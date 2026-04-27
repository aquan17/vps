<?php

namespace App\Console\Commands;

use App\Models\VpsInstance;
use App\Services\GcpVpsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteExpiredVps extends Command
{
    protected $signature = 'vps:cleanup';

    protected $description = 'Delete all expired VPS instances from GCP and database';

    public function handle(GcpVpsService $gcpService)
    {
        $this->info('Finding expired VPS instances...');

        $expiredInstances = VpsInstance::with('gcpProject')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredInstances->isEmpty()) {
            $this->info('No expired instances found.');
            return 0;
        }

        foreach ($expiredInstances as $vps) {
            $this->info("Deleting VPS: {$vps->name} (Expired at: {$vps->expires_at})");

            try {
                if (!$vps->gcpProject) {
                    throw new \RuntimeException('Missing GCP project for VPS #' . $vps->id);
                }

                $gcpService->setProjectSettings(
                    $vps->gcpProject->project_id,
                    $vps->gcpProject->credentials_path
                );

                if (!$gcpService->deleteInstance($vps->name, $vps->zone)) {
                    throw new \RuntimeException('GCP delete returned false.');
                }

                $vps->delete();

                Log::info('Expired VPS deleted', [
                    'vps_id' => $vps->id,
                    'name' => $vps->name,
                    'project_id' => $vps->gcpProject->project_id,
                ]);

                $this->info('- Deleted successfully.');
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'was not found')) {
                    $this->warn('- Not found on GCP, cleaning database anyway...');
                    $vps->delete();

                    Log::info('Expired VPS missing on GCP, database record deleted', [
                        'vps_id' => $vps->id,
                        'name' => $vps->name,
                    ]);

                    continue;
                }

                Log::error('Failed to delete expired VPS', [
                    'vps_id' => $vps->id,
                    'name' => $vps->name,
                    'message' => $e->getMessage(),
                ]);

                $this->error('- Failed to delete GCP instance: ' . $e->getMessage());
            }
        }

        $this->info('Cleanup complete!');
        return 0;
    }
}
