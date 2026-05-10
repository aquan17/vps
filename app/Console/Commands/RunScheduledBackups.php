<?php

namespace App\Console\Commands;

use App\Models\VpsInstance;
use App\Services\GcpBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledBackups extends Command
{
    protected $signature = 'vps:backup-run';

    protected $description = 'Chạy sao lưu VPS theo lịch và xoá theo retention';

    public function handle(GcpBackupService $backupService): int
    {
        $now = now('UTC');
        $hour = (int) $now->format('G');
        $weekday = (int) $now->format('w'); // 0 (Sun) - 6 (Sat)

        $candidates = VpsInstance::with(['gcpProject', 'backups'])
            ->where('backup_enabled', true)
            ->whereIn('backup_schedule', ['daily', 'weekly'])
            ->get();

        foreach ($candidates as $vps) {
            if (!$vps->gcpProject) {
                continue;
            }

            if ((int) $vps->backup_hour_utc !== $hour) {
                continue;
            }

            if ($vps->backup_schedule === 'weekly' && (int) $vps->backup_weekday_utc !== $weekday) {
                continue;
            }

            if ($vps->backup_last_run_at && $vps->backup_last_run_at->gt($now->copy()->subHours(23))) {
                continue;
            }

            try {
                $backupService->setProjectSettings($vps->gcpProject->project_id, $vps->gcpProject->credentials_path);
                $backupService->createSnapshotBackup($vps, 1);

                $vps->backup_last_run_at = now('UTC');
                $vps->save();

                $retentionDays = max(1, (int) $vps->backup_retention_days);
                $staleBackups = $vps->backups()
                    ->where('status', 'READY')
                    ->where('created_at', '<', now()->subDays($retentionDays))
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($staleBackups as $stale) {
                    try {
                        $backupService->deleteSnapshotBackup($stale);
                        $stale->delete();
                    } catch (\Throwable $retentionError) {
                        Log::warning('Backup retention delete failed', [
                            'vps_id' => $vps->id,
                            'backup_id' => $stale->id,
                            'snapshot_name' => $stale->snapshot_name,
                            'message' => $retentionError->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Scheduled backup failed', [
                    'vps_id' => $vps->id,
                    'name' => $vps->name,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }
}
