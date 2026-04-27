<?php

namespace App\Jobs;

use App\Models\VpsInstance;
use App\Services\GcpVpsService;
use App\Services\VpsFirewallPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncVpsFirewallRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;

    public int $vpsId;
    public array $staleRuleNames;

    public function __construct(int $vpsId, array $staleRuleNames = [])
    {
        $this->vpsId = $vpsId;
        $this->staleRuleNames = $staleRuleNames;
    }

    public function handle(GcpVpsService $gcp): void
    {
        $vps = VpsInstance::with(['gcpProject', 'firewallRules'])->find($this->vpsId);

        if (!$vps || !$vps->gcpProject) {
            return;
        }

        $gcp->setProjectSettings($vps->gcpProject->project_id, $vps->gcpProject->credentials_path);

        foreach (array_unique(array_filter($this->staleRuleNames)) as $ruleName) {
            $gcp->deleteFirewallRule($ruleName);
        }

        // Remove old per-port rules from the previous implementation. The grouped
        // rules are recreated below and are safe to delete/recreate idempotently.
        foreach ($vps->firewallRules->pluck('rule_name')->filter()->unique() as $legacyRuleName) {
            $gcp->deleteFirewallRule($legacyRuleName);
        }

        $groups = $vps->firewallRules->groupBy(function ($rule) {
            return $rule->protocol . '|' . $rule->source_range;
        });

        foreach ($groups as $groupKey => $rules) {
            [$protocol, $sourceRange] = explode('|', $groupKey, 2);
            $gcpRuleName = VpsFirewallPolicy::groupRuleName($vps, $protocol, $sourceRange);
            $portRanges = VpsFirewallPolicy::mergePortRanges($rules);

            try {
                $targetTag = $gcp->syncFirewallRuleGroup($vps, $protocol, $sourceRange, $portRanges, $gcpRuleName);

                $rules->each->update([
                    'target_tag' => $targetTag,
                    'sync_status' => 'active',
                    'sync_error' => null,
                    'synced_at' => now(),
                ]);

                Log::info('Synced VPS firewall group', [
                    'vps_id' => $vps->id,
                    'rule_name' => $gcpRuleName,
                    'protocol' => $protocol,
                    'source_range' => $sourceRange,
                    'ports' => $portRanges,
                ]);
            } catch (Throwable $e) {
                $rules->each->update([
                    'sync_status' => 'failed',
                    'sync_error' => $e->getMessage(),
                ]);

                Log::error('Failed to sync VPS firewall group', [
                    'vps_id' => $vps->id,
                    'rule_name' => $gcpRuleName,
                    'message' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }
}
