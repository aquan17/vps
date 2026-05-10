<?php

namespace App\Services;

use App\Models\VpsBackup;
use App\Models\VpsInstance;
use Google\Cloud\Compute\V1\Client\DisksClient;
use Google\Cloud\Compute\V1\Client\InstancesClient;
use Google\Cloud\Compute\V1\Client\SnapshotsClient;
use Google\Cloud\Compute\V1\DeleteSnapshotRequest;
use Google\Cloud\Compute\V1\CreateSnapshotDiskRequest;
use Google\Cloud\Compute\V1\GetInstanceRequest;
use Google\Cloud\Compute\V1\GetSnapshotRequest;
use Google\Cloud\Compute\V1\Snapshot;
use Google\ApiCore\OperationResponse;

class GcpBackupService
{
    protected ?string $projectId;
    protected ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = null;
        $this->credentialsPath = null;
    }

    public function setProjectSettings(string $projectId, string $credentialsFile): void
    {
        $this->projectId = $projectId;
        $credentialsFile = trim($credentialsFile);

        if ($this->isAbsolutePath($credentialsFile)) {
            $this->credentialsPath = $this->normalizePathSeparators($credentialsFile);
            return;
        }

        $filename = ltrim($this->normalizePathSeparators($credentialsFile), DIRECTORY_SEPARATOR);
        $this->credentialsPath = storage_path('app/gcp_credentials/' . $filename);
    }

    public function createSnapshotBackup(VpsInstance $vps, int $adminUserId): VpsBackup
    {
        $diskName = $this->getBootDiskName($vps->name, $vps->zone);
        $snapshotName = $this->buildSnapshotName($vps);

        $backup = VpsBackup::create([
            'vps_instance_id' => $vps->id,
            'gcp_project_id' => $vps->gcp_project_id,
            'snapshot_name' => $snapshotName,
            'source_disk' => $diskName,
            'status' => 'CREATING',
            'created_by' => $adminUserId,
        ]);

        try {
            $disksClient = new DisksClient([
                'credentials' => $this->credentialsPath,
            ]);

            $snapshot = new Snapshot();
            $snapshot->setName($snapshotName);
            $snapshot->setDescription('CloudVPS backup for VPS #' . $vps->id . ' (' . $vps->name . ')');

            $request = new CreateSnapshotDiskRequest();
            $request->setProject($this->projectId);
            $request->setZone($vps->zone);
            $request->setDisk($diskName);
            $request->setSnapshotResource($snapshot);

            $operation = $disksClient->createSnapshot($request);
            $completed = $this->waitForZoneOperation($operation);

            $backup->update([
                'status' => $completed ? 'READY' : 'CREATING',
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $backup->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $backup->fresh();
    }

    public function deleteSnapshotBackup(VpsBackup $backup): void
    {
        $client = new SnapshotsClient([
            'credentials' => $this->credentialsPath,
        ]);

        $request = new DeleteSnapshotRequest();
        $request->setProject($this->projectId);
        $request->setSnapshot($backup->snapshot_name);

        $operation = $client->delete($request);
        $operation->pollUntilComplete();
    }

    public function refreshSnapshotStatus(VpsBackup $backup): VpsBackup
    {
        $client = new SnapshotsClient([
            'credentials' => $this->credentialsPath,
        ]);

        $request = new GetSnapshotRequest();
        $request->setProject($this->projectId);
        $request->setSnapshot($backup->snapshot_name);

        try {
            $snapshot = $client->get($request);
            $status = strtoupper((string) $snapshot->getStatus());
            $mappedStatus = $status === 'READY' ? 'READY' : ($status === 'FAILED' ? 'FAILED' : 'CREATING');

            $backup->update([
                'status' => $mappedStatus,
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'not found')) {
                $backup->update([
                    'status' => 'DELETED',
                    'error_message' => null,
                ]);
            } else {
                $backup->update([
                    'status' => 'FAILED',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $backup->fresh();
    }

    private function getBootDiskName(string $instanceName, string $zone): string
    {
        $instancesClient = new InstancesClient([
            'credentials' => $this->credentialsPath,
        ]);

        $request = new GetInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstance($instanceName);

        $instance = $instancesClient->get($request);

        foreach ($instance->getDisks() as $disk) {
            if (!$disk->getBoot()) {
                continue;
            }

            $source = (string) $disk->getSource();
            if ($source !== '') {
                return basename($source);
            }
        }

        throw new \RuntimeException('Could not detect boot disk for this VPS.');
    }

    private function buildSnapshotName(VpsInstance $vps): string
    {
        return strtolower(sprintf('vps-%d-%s', $vps->id, now()->format('Ymd-His')));
    }

    private function waitForZoneOperation(OperationResponse $operation): bool
    {
        $completed = $operation->pollUntilComplete([
            'initialPollDelayMillis' => 1000,
            'maxPollDelayMillis' => 5000,
            // Snapshot can take several minutes depending on disk size/change rate.
            'totalPollTimeoutMillis' => 300000,
        ]);

        if (!$completed) {
            return false;
        }

        if (!$operation->operationFailed()) {
            return true;
        }

        throw new \RuntimeException('GCP backup operation failed.');
    }

    private function normalizePathSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }
}
