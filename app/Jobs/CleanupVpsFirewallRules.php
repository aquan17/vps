<?php

namespace App\Jobs;

use App\Services\GcpVpsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupVpsFirewallRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;

    public string $projectId;
    public string $credentialsFile;
    public array $ruleNames;

    public function __construct(string $projectId, string $credentialsFile, array $ruleNames)
    {
        $this->projectId = $projectId;
        $this->credentialsFile = $credentialsFile;
        $this->ruleNames = $ruleNames;
    }

    public function handle(GcpVpsService $gcp): void
    {
        $gcp->setProjectSettings($this->projectId, $this->credentialsFile);

        foreach (array_unique(array_filter($this->ruleNames)) as $ruleName) {
            if (!$gcp->deleteFirewallRule($ruleName)) {
                Log::warning('Failed to cleanup GCP firewall rule', [
                    'project_id' => $this->projectId,
                    'rule_name' => $ruleName,
                ]);
            }
        }
    }
}
