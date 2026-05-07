<?php

namespace App\Http\Controllers;

use App\Jobs\CleanupVpsFirewallRules;
use App\Jobs\SyncVpsFirewallRules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\VpsInstance;
use App\Models\VpsFirewallRule;
use App\Models\GcpProject;
use App\Models\User;
use App\Services\GcpVpsService;
use App\Services\GcpProjectRouter;
use App\Services\VpsPricingService;
use App\Services\VpsFirewallPolicy;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VpsController extends Controller
{
    protected GcpVpsService $gcp;
    protected GcpProjectRouter $router;
    protected VpsPricingService $pricingService;
    protected VoucherService $voucherService;

    public function __construct(GcpVpsService $gcp, GcpProjectRouter $router, VpsPricingService $pricingService, VoucherService $voucherService)
    {
        $this->gcp            = $gcp;
        $this->router         = $router;
        $this->pricingService = $pricingService;
        $this->voucherService = $voucherService;
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function index()
    {
        $instances = VpsInstance::forUser(Auth::id())
            ->with('gcpProject')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->syncPendingInstancesAfterResponse($instances->pluck('id')->all());

        return view('vps.index', compact('instances'));
    }

    private function syncPendingInstancesAfterResponse(array $instanceIds): void
    {
        if (empty($instanceIds)) {
            return;
        }

        app()->terminating(function () use ($instanceIds) {
            $instances = VpsInstance::with('gcpProject')
                ->whereIn('id', $instanceIds)
                ->get();

            $this->syncPendingInstances($instances);
        });
    }

    private function syncPendingInstances($instances): void
    {
        foreach ($instances as $instance) {
            if (!$instance->gcpProject || !$instance->isProvisioning()) {
                continue;
            }

            $cacheKey = 'vps_status_sync_' . $instance->id;
            if (!Cache::add($cacheKey, true, 20)) {
                continue;
            }

            try {
                $this->gcp->setProjectSettings($instance->gcpProject->project_id, $instance->gcpProject->credentials_path);
                $this->gcp->syncInstanceStatus($instance);
            } catch (\Exception $e) {
                Log::warning('Failed to sync pending VPS status', [
                    'vps_id' => $instance->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    // ─── Admin: GCP Project Management ───────────────────────────────────────

    public function adminGoogleCloud()
    {
        abort_unless(Auth::user()->is_admin, 403);

        $gcpProjects = GcpProject::withCount([
                'instances as active_instances_count' => function ($query) {
                    $query->where('status', '!=', 'Lỗi API');
                },
            ])
            ->withSum([
                'instances as active_cpu_used' => function ($query) {
                    $query->where('status', '!=', 'Lỗi API');
                },
            ], 'cpu')
            ->withSum([
                'instances as active_ram_used' => function ($query) {
                    $query->where('status', '!=', 'Lỗi API');
                },
            ], 'ram')
            ->orderBy('created_at', 'desc')
            ->get();

        $allInstances = VpsInstance::with(['user', 'gcpProject'])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $this->syncPendingInstancesAfterResponse($allInstances->getCollection()->pluck('id')->all());

        $projects = [];
        $totalCpuUsed  = 0;
        $totalCpuLimit = 0;
        $totalInst     = 0;
        $totalInstLimit = 0;
        $missingQuotaProjectIds = [];

        foreach ($gcpProjects as $pj) {
            $vpsCount  = (int) $pj->active_instances_count;
            $ramUsed   = (int) $pj->active_ram_used;
            $cpuDbUsed = (int) $pj->active_cpu_used;

            // Read cached quota only. Missing cache is refreshed after the page response.
            $quota = Cache::get($this->gcpQuotaCacheKey($pj->id));
            if ($quota === null) {
                $missingQuotaProjectIds[] = $pj->id;
            }

            $cpuLimit  = $quota['CPUS_ALL_REGIONS']['limit'] ?? $quota['CPUS']['limit'] ?? 12;
            $cpuUsed   = $quota['CPUS_ALL_REGIONS']['usage'] ?? $quota['CPUS']['usage'] ?? $cpuDbUsed;
            $instLimit = $quota['INSTANCES']['limit'] ?? 8;
            $ramLimit  = $cpuLimit * 8;

            $hasCpuRoom      = $cpuLimit <= 0 || $cpuUsed < $cpuLimit;
            $hasInstanceRoom = $instLimit <= 0 || $vpsCount < $instLimit;
            $shouldBeFull    = !$hasCpuRoom || !$hasInstanceRoom;

            if ($quota !== null && $pj->is_full !== $shouldBeFull) {
                $pj->is_full = $shouldBeFull;
                $pj->save();
            }

            // Build display name from JSON file
            $displayName = $pj->project_id;
            try {
                $j = json_decode(file_get_contents($pj->credentials_path), true);
                if (isset($j['client_email'])) {
                    $displayName = explode('@', $j['client_email'])[0];
                }
            } catch (\Exception $e) {}

            $projects[] = [
                'id'         => $pj->id,
                'name'       => $displayName,
                'project_id' => $pj->project_id,
                'is_active'  => $pj->is_active,
                'is_full'    => $pj->is_full,
                'cpu_used'   => $cpuUsed,
                'cpu_limit'  => $cpuLimit,
                'ram_used'   => $ramUsed,
                'ram_limit'  => $ramLimit,
                'vps_count'  => $vpsCount,
                'inst_limit' => $instLimit,
            ];

            $totalCpuUsed   += $cpuUsed;
            $totalCpuLimit  += $cpuLimit;
            $totalInst      += $vpsCount;
            $totalInstLimit += $instLimit;
        }

        $this->refreshGcpQuotasAfterResponse($missingQuotaProjectIds);

        $stats = [
            'active'     => $gcpProjects->where('is_active', true)->where('is_full', false)->count(),
            'full'       => $gcpProjects->where('is_full', true)->count(),
            'cpu_used'   => $totalCpuUsed,
            'cpu_limit'  => $totalCpuLimit,
            'instances'  => $totalInst,
            'inst_limit' => $totalInstLimit,
        ];

        return view('admin.google-cloud', compact('projects', 'allInstances', 'stats'));
    }

    private function gcpQuotaCacheKey(int $projectId): string
    {
        return 'gcp_quota_' . $projectId;
    }

    private function gcpQuotaRefreshLockKey(int $projectId): string
    {
        return 'gcp_quota_refresh_' . $projectId;
    }

    private function refreshGcpQuotasAfterResponse(array $projectIds): void
    {
        $projectIds = array_values(array_unique(array_filter($projectIds)));
        if (empty($projectIds)) {
            return;
        }

        app()->terminating(function () use ($projectIds) {
            GcpProject::whereIn('id', $projectIds)->get()->each(function (GcpProject $project) {
                $lockKey = $this->gcpQuotaRefreshLockKey($project->id);
                if (!Cache::add($lockKey, true, 60)) {
                    return;
                }

                try {
                    $this->refreshGcpQuotaCache($project);
                } finally {
                    Cache::forget($lockKey);
                }
            });
        });
    }

    private function refreshGcpQuotaCache(GcpProject $project): ?array
    {
        try {
            $this->gcp->setProjectSettings($project->project_id, $project->credentials_path);
            $quota = $this->gcp->getRegionQuotas('asia-southeast1');
            $quota = array_merge($quota, $this->gcp->getProjectQuotas());

            Cache::put($this->gcpQuotaCacheKey($project->id), $quota, 300);

            return $quota;
        } catch (\Exception $e) {
            Log::warning('Failed to refresh GCP quota cache', [
                'gcp_project_id' => $project->id,
                'project_id' => $project->project_id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function adminGcpStore(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $request->validate(['credentials' => 'required|file|mimes:json|max:2048']);

        try {
            $fileContent = file_get_contents($request->file('credentials')->getRealPath());
            $json = json_decode($fileContent, true);

            if (!$json || !isset($json['project_id']) || !isset($json['private_key'])) {
                return back()->with('error', 'File JSON không đúng định dạng Service Account của Google Cloud!');
            }

            $projectId = $json['project_id'];

            if (GcpProject::where('project_id', $projectId)->exists()) {
                return back()->with('error', "Project [{$projectId}] đã tồn tại trong hệ thống!");
            }

            // Store credentials securely
            $fileName = 'gcp_' . $projectId . '_' . time() . '.json';
            $path     = $request->file('credentials')->storeAs('gcp_credentials', $fileName, 'local');
            $fullPath = storage_path('app/' . $path);

            // Quick API test
            try {
                $this->gcp->setProjectSettings($projectId, $fullPath);
                $this->gcp->getRegionQuotas('asia-southeast1');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete('gcp_credentials/' . $fileName);
                return back()->with('error', 'Kết nối API thất bại! File JSON không hợp lệ hoặc Service Account thiếu quyền. Chi tiết: ' . $e->getMessage());
            }

            GcpProject::create([
                'project_id'       => $projectId,
                'credentials_file' => $fileName,
                'is_active'        => true,
                'is_full'          => false,
            ]);

            return back()->with('success', "✅ Đã thêm thành công Project [{$projectId}]. Hệ thống đã kích hoạt cân bằng tải tự động!");

        } catch (\Exception $e) {
            Log::error('Upload GCP JSON failed', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function adminGcpSync()
    {
        abort_unless(Auth::user()->is_admin, 403);
        $projectIds = GcpProject::pluck('id')->all();
        foreach ($projectIds as $projectId) {
            Cache::forget($this->gcpQuotaCacheKey($projectId));
        }
        Cache::forget('gcp_total_quotas');
        $this->refreshGcpQuotasAfterResponse($projectIds);
        return back()->with('success', '🔄 Đã đồng bộ xong! Thống kê Quota đã được tải mới từ Google Cloud.');
    }

    public function adminGcpSyncVps()
    {
        abort_unless(Auth::user()->is_admin, 403);

        $synced = 0;
        $failed = 0;

        $instances = VpsInstance::with('gcpProject')
            ->whereNotNull('gcp_project_id')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($instances as $instance) {
            if (!$instance->gcpProject) {
                $failed++;
                continue;
            }

            try {
                $this->gcp->setProjectSettings($instance->gcpProject->project_id, $instance->gcpProject->credentials_path);
                $this->gcp->syncInstanceStatus($instance);
                $synced++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Admin failed to sync VPS status', [
                    'vps_id' => $instance->id,
                    'name' => $instance->name,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $message = "Da dong bo trang thai {$synced} VPS.";
        if ($failed > 0) {
            $message .= " {$failed} VPS bi loi, xem log de biet chi tiet.";
        }

        return back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    public function adminGcpToggle(Request $request, $id)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $pj    = GcpProject::findOrFail($id);
        $field = $request->input('field');

        if (!in_array($field, ['is_active', 'is_full'])) {
            return back()->with('error', 'Trường cần thay đổi không hợp lệ.');
        }

        $pj->$field = !$pj->$field;
        $pj->save();
        Cache::forget('gcp_quota_' . $pj->id);

        $label = $field === 'is_active'
            ? ($pj->is_active ? 'Đã bật' : 'Đã tắt')
            : ($pj->is_full ? 'Đánh dấu đầy' : 'Đã mở lại');

        return back()->with('success', "{$label} Project [{$pj->project_id}] thành công!");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function adminVpsExpiresAt(Request $request, $id)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $data = $request->validate([
            'expires_at' => 'required|date_format:Y-m-d',
        ]);

        $vps = VpsInstance::findOrFail($id);
        $vps->expires_at = Carbon::createFromFormat('Y-m-d', $data['expires_at'], 'Asia/Ho_Chi_Minh')->endOfDay();
        $vps->save();

        return back()->with('success', 'Da cap nhat ngay het han VPS ' . $vps->name . '.');
    }

    private function findManageableInstance($id)
    {
        $query = VpsInstance::where('id', $id);

        if (!Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        return $query->firstOrFail();
    }

    private function allowedOsImageProjects(): array
    {
        return [
            'windows-cloud',
            'ubuntu-os-cloud',
            'debian-cloud',
            'rocky-linux-cloud',
            'almalinux-cloud',
            'centos-cloud',
        ];
    }

    private function isAllowedOsImageSelection($value): bool
    {
        if (!is_string($value) || !str_contains($value, ':')) {
            return false;
        }

        [$project, $family] = explode(':', $value, 2);

        $allowedImages = array_merge(...array_values($this->osImageOptions()));

        return in_array($project, $this->allowedOsImageProjects(), true)
            && preg_match('/^[a-z0-9][a-z0-9-]{1,80}[a-z0-9]$/', $family)
            && array_key_exists($value, $allowedImages);
    }

    private function allowedDurationsForUser(?\App\Models\User $user): array
    {
        if ($user && $user->is_admin) {
            return [1, 7, 30, 90, 180, 365];
        }

        return [1, 7, 30, 90, 180, 365];
    }

    private function osTypeFromImageSelection(string $value): string
    {
        [$project, $family] = explode(':', $value, 2);

        if ($project === 'windows-cloud') {
            return 'windows';
        }

        if ($project === 'ubuntu-os-cloud') {
            return 'ubuntu';
        }

        if ($project === 'debian-cloud') {
            return 'debian';
        }

        if ($project === 'rocky-linux-cloud') {
            return 'rocky';
        }

        if ($project === 'almalinux-cloud') {
            return 'almalinux';
        }

        if ($project === 'centos-cloud') {
            return str_contains($family, 'stream') ? 'centos-stream' : 'centos';
        }

        return 'linux';
    }

    private function osImageOptions(): array
    {
        return [
            'windows' => [
                'windows-cloud:windows-2016' => 'Windows Server 2016',
                'windows-cloud:windows-2019' => 'Windows Server 2019',
                'windows-cloud:windows-2022' => 'Windows Server 2022',
                'windows-cloud:windows-2025' => 'Windows Server 2025',
            ],
            'ubuntu' => [
                'ubuntu-os-cloud:ubuntu-2204-lts' => 'Ubuntu 22.04 LTS',
                'ubuntu-os-cloud:ubuntu-2404-lts' => 'Ubuntu 24.04 LTS',
            ],
            'linux' => [
                'debian-cloud:debian-11' => 'Debian 11',
                'debian-cloud:debian-12' => 'Debian 12',
                'rocky-linux-cloud:rocky-linux-8' => 'Rocky Linux 8',
                'rocky-linux-cloud:rocky-linux-9' => 'Rocky Linux 9',
                'almalinux-cloud:almalinux-8' => 'AlmaLinux 8',
                'almalinux-cloud:almalinux-9' => 'AlmaLinux 9',
                'centos-cloud:centos-stream-9' => 'CentOS Stream 9',
            ],
        ];
    }

    // ─── VPS CRUD ─────────────────────────────────────────────────────────────

    private function defaultRemotePortForOs(string $osType): int
    {
        return $osType === 'windows' ? 3389 : 22;
    }

    private function createDefaultRemoteFirewallRule(VpsInstance $vps, string $osType, ?int $ownerUserId = null, bool $dispatchSync = true): void
    {
        $port = $this->defaultRemotePortForOs($osType);
        $sourceRange = '0.0.0.0/0';
        $ownerUserId = $ownerUserId ?: $vps->user_id;

        VpsFirewallRule::firstOrCreate(
            [
                'vps_instance_id' => $vps->id,
                'protocol' => 'tcp',
                'port_start' => $port,
                'port_end' => $port,
                'source_range' => $sourceRange,
            ],
            [
                'user_id' => $ownerUserId,
                'gcp_project_id' => $vps->gcp_project_id,
                'rule_name' => VpsFirewallPolicy::entryRuleName($vps, 'tcp', $port, $port, $sourceRange),
                'target_tag' => GcpVpsService::firewallTargetTag($vps),
                'sync_status' => 'pending',
            ]
        );

        if ($dispatchSync) {
            SyncVpsFirewallRules::dispatch($vps->id);
        }
    }

    private function fallbackZonesFor(string $selectedZone): array
    {
        $region = substr($selectedZone, 0, strrpos($selectedZone, '-'));

        $zonesByRegion = [
            'asia-southeast1' => ['asia-southeast1-a', 'asia-southeast1-c', 'asia-southeast1-b'],
            'asia-east1' => ['asia-east1-a', 'asia-east1-c', 'asia-east1-b'],
            'asia-east2' => ['asia-east2-a', 'asia-east2-c', 'asia-east2-b'],
            'asia-northeast1' => ['asia-northeast1-a', 'asia-northeast1-c', 'asia-northeast1-b'],
            'us-west1' => ['us-west1-a', 'us-west1-c', 'us-west1-b'],
            'europe-west2' => ['europe-west2-a', 'europe-west2-c', 'europe-west2-b'],
            'europe-west3' => ['europe-west3-a', 'europe-west3-c', 'europe-west3-b'],
        ];

        return array_values(array_unique(array_merge(
            [$selectedZone],
            $zonesByRegion[$region] ?? [],
            ['us-central1-a', 'us-central1-c']
        )));
    }

    private function isRetryableCreateError(\Throwable $e): bool
    {
        $message = strtoupper($e->getMessage());

        return str_contains($message, 'SERVICE UNAVAILABLE')
            || str_contains($message, 'ZONE_RESOURCE_POOL_EXHAUSTED')
            || str_contains($message, 'RESOURCE_POOL_EXHAUSTED');
    }

    public function create()
    {
        $plans       = $this->pricingService->getPlans();
        $gcpProject  = GcpProject::first();
        $defaultName = 'vps-' . strtolower(\Illuminate\Support\Str::random(5));
        $osImages    = $this->osImageOptions();
        $adminAssignableUsers = Auth::user()->is_admin
            ? User::query()
                ->select('id', 'name', 'email', 'balance', 'is_admin')
                ->orderBy('id')
                ->get()
            : collect();

        $windowsImages = $osImages['windows'];
        $ubuntuImages  = $osImages['ubuntu'];
        $linuxImages   = $osImages['linux'];
        $uiZones       = [
            'asia-southeast1' => ['name' => 'Singapore',    'flag' => 'SG', 'ping' => '30ms',  'id' => 'asia-southeast1-b'],
            'asia-east2'      => ['name' => 'Hong Kong',    'flag' => 'HK', 'ping' => '36ms',  'id' => 'asia-east2-b'],
            'asia-east1'      => ['name' => 'Taiwan',       'flag' => 'TW', 'ping' => '42ms',  'id' => 'asia-east1-b'],
            'asia-northeast1' => ['name' => 'Tokyo',        'flag' => 'JP', 'ping' => '65ms',  'id' => 'asia-northeast1-b'],
            'us-west1'        => ['name' => 'Oregon USA',   'flag' => 'US', 'ping' => '180ms', 'id' => 'us-west1-b'],
            'europe-west2'    => ['name' => 'London UK',    'flag' => 'UK', 'ping' => '235ms', 'id' => 'europe-west2-b'],
            'europe-west3'    => ['name' => 'Frankfurt DE', 'flag' => 'DE', 'ping' => '250ms', 'id' => 'europe-west3-b'],
        ];

        return view('vps.create', compact('plans', 'windowsImages', 'ubuntuImages', 'linuxImages', 'uiZones', 'defaultName', 'adminAssignableUsers'));

        if ($gcpProject) {
            $this->gcp->setProjectSettings($gcpProject->project_id, $gcpProject->credentials_file);

            $apiZones = Cache::remember('gcp_zones', 43200, function () {
                return $this->gcp->getAvailableZones();
            });

            $zoneMappings = [
                'asia-southeast1' => ['name' => 'Singapore',    'flag' => '🇸🇬', 'ping' => '30ms',  'color' => 'var(--green)', 'bg' => 'rgba(16,185,129,0.2)'],
                'asia-east2'      => ['name' => 'Hong Kong',    'flag' => '🇭🇰', 'ping' => '36ms',  'color' => 'var(--green)', 'bg' => 'rgba(16,185,129,0.2)'],
                'asia-east1'      => ['name' => 'Taiwan',       'flag' => '🇹🇼', 'ping' => '42ms',  'color' => 'var(--green)', 'bg' => 'rgba(16,185,129,0.2)'],
                'asia-northeast1' => ['name' => 'Tokyo',        'flag' => '🇯🇵', 'ping' => '65ms',  'color' => '#f59e0b',      'bg' => 'rgba(245,158,11,0.2)'],
                'us-west1'        => ['name' => 'Oregon USA',   'flag' => '🇺🇸', 'ping' => '180ms', 'color' => 'var(--red)',   'bg' => 'rgba(239,68,68,0.2)'],
                'europe-west3'    => ['name' => 'Frankfurt DE', 'flag' => '🇩🇪', 'ping' => '250ms', 'color' => 'var(--red)',   'bg' => 'rgba(239,68,68,0.2)'],
            ];

            foreach ($apiZones as $zone) {
                $prefix = substr($zone, 0, strrpos($zone, '-'));
                if (isset($zoneMappings[$prefix]) && !isset($uiZones[$prefix])) {
                    $uiZones[$prefix]       = $zoneMappings[$prefix];
                    $uiZones[$prefix]['id'] = $zone;
                }
            }

            $machineSpecs = Cache::remember('gcp_machine_specs', 43200, function () use ($plans) {
                $types = array_column($plans, 'type');
                return $this->gcp->getMachineTypeSpecs('asia-southeast1-b', $types);
            });

            foreach ($plans as $key => &$plan) {
                if (isset($machineSpecs[$plan['type']])) {
                    $plan['api_cores'] = $machineSpecs[$plan['type']]['guestCpus'];
                    $plan['api_ram']   = round($machineSpecs[$plan['type']]['memoryMb'] / 1024);
                }
            }
        }

        return view('vps.create', compact('plans', 'windowsImages', 'ubuntuImages', 'linuxImages', 'uiZones', 'defaultName', 'adminAssignableUsers'));
    }

    public function previewVoucher(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => 'required|string',
            'duration' => ['required', 'integer', Rule::in($this->allowedDurationsForUser(Auth::user()))],
            'voucher_code' => 'nullable|string|max:50',
            'user_id' => [
                Rule::requiredIf(Auth::user()->is_admin),
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        $voucherUser = Auth::user()->is_admin
            ? User::findOrFail((int) $data['user_id'])
            : Auth::user();

        $plans = $this->pricingService->getPlans();
        $planId = array_key_exists($data['plan'], $plans) ? $data['plan'] : 'plan_mini';
        $plan = $plans[$planId];
        $duration = (int) $data['duration'];
        $subtotal = $this->pricingService->calculatePrice($plan, $duration);

        try {
            $result = $this->voucherService->preview($data['voucher_code'] ?? null, $voucherUser, $planId, $duration, $subtotal);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'discount_amount' => 0,
                'original_amount' => $subtotal,
                'final_amount' => $subtotal,
            ], 422);
        }

        return response()->json([
            'code' => $result['code'],
            'discount_amount' => $result['discount_amount'],
            'original_amount' => $result['original_amount'],
            'final_amount' => $result['final_amount'],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|min:3|max:32|regex:/^[a-z]([a-z0-9-]{1,30}[a-z0-9])$/|unique:vps_instances,name',
            'plan'     => 'required',
            'os_image' => [
                'required',
                'string',
                'max:120',
                function ($attribute, $value, $fail) {
                    if (!$this->isAllowedOsImageSelection($value)) {
                        $fail('Image he dieu hanh khong hop le.');
                    }
                },
            ],
            'zone'     => 'required',
            'duration' => ['required', 'integer', Rule::in($this->allowedDurationsForUser(Auth::user()))],
            'voucher_code' => 'nullable|string|max:50',
            'user_id' => [
                Rule::requiredIf(Auth::user()->is_admin),
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        $ownerUserId = Auth::user()->is_admin
            ? (int) $request->input('user_id')
            : Auth::id();

        // $requestedDuration = (int) $request->input('duration');
        // if ($requestedDuration === 1) {
        //     $createdInLastDay = VpsInstance::query()
        //         ->where('user_id', $ownerUserId)
        //         ->where('created_at', '>=', now()->subDay())
        //         ->exists();

        //     if ($createdInLastDay) {
        //         return back()
        //             ->withInput()
        //             ->with('error', 'Goi 1 ngay: moi tai khoan chi duoc tao toi da 1 VPS trong 24 gio.');
        //     }
        // }

        $plans = $this->pricingService->getPlans();

        $plan       = $plans[$request->plan] ?? $plans['plan_mini'];
        $planId     = array_key_exists($request->plan, $plans) ? $request->plan : 'plan_mini';
        $zone       = $request->zone;
        $duration   = (int) $request->duration;
        $basePrice  = $this->pricingService->calculatePrice($plan, $duration);
        $totalPrice = $basePrice;
        $osType     = $this->osTypeFromImageSelection($request->os_image);
        $voucherCode = $this->voucherService->normalizeCode($request->input('voucher_code'));

        $vpsName        = $request->input('name');
        $randomPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#*&^'), 0, 14);

        try {
            $gcpProject = null;
            $region = substr($zone, 0, strrpos($zone, '-'));

            foreach ($this->router->getAvailableProjects() as $candidateProject) {
                $this->gcp->setProjectSettings($candidateProject->project_id, $candidateProject->credentials_path);

                $quotaCheck = $this->gcp->hasQuotaForPlan($region, (int) $plan['cores'], (int) $plan['disk']);

                if ($quotaCheck['ok']) {
                    $gcpProject = $candidateProject;
                    break;
                }

                Log::warning('Skip GCP project: not enough quota for selected plan', [
                    'user_id' => $ownerUserId,
                    'actor_user_id' => Auth::id(),
                    'gcp_project_id' => $candidateProject->id,
                    'project_id' => $candidateProject->project_id,
                    'metric' => $quotaCheck['metric'] ?? null,
                    'limit' => $quotaCheck['limit'] ?? null,
                    'usage' => $quotaCheck['usage'] ?? null,
                    'needed' => $quotaCheck['needed'] ?? null,
                    'available' => $quotaCheck['available'] ?? null,
                ]);
            }

            if (!$gcpProject) {
                return back()->with('error', 'Tạm hết hàng cho gói đã chọn. Vui lòng chọn gói nhỏ hơn hoặc thử lại sau.');
            }
        } catch (\Exception $e) {
            Log::warning('No available VPS stock', [
                'user_id' => $ownerUserId,
                'actor_user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Tạm hết hàng. Vui lòng quay lại sau.');
        }

        $expiresAt = now('Asia/Ho_Chi_Minh')->addDays($duration);

        try {
            $vps = DB::transaction(function () use ($ownerUserId, $gcpProject, $vpsName, $randomPassword, $zone, $plan, $planId, $expiresAt, $osType, $duration, $basePrice, $voucherCode, &$totalPrice) {
                $user = User::whereKey($ownerUserId)->lockForUpdate()->firstOrFail();

                $voucherPreview = $this->voucherService->preview($voucherCode, $user, $planId, $duration, $basePrice);
                $totalPrice = (int) $voucherPreview['final_amount'];

                if ((int) $user->balance < $totalPrice) {
                    throw new \RuntimeException('INSUFFICIENT_BALANCE');
                }

                $vps = VpsInstance::create([
                    'user_id'        => $user->id,
                    'gcp_project_id' => $gcpProject->id,
                    'name'           => $vpsName,
                    'password'       => $randomPassword,
                    'zone'           => $zone,
                    'machine_type'   => $plan['type'],
                    'status'         => 'Đang khởi tạo...',
                    'expires_at'     => $expiresAt,
                    'os'             => $osType,
                    'cpu'            => $plan['cores'],
                    'ram'            => $plan['ram'],
                    'disk'           => $plan['disk'],
                    'voucher_code'   => $voucherPreview['code'],
                    'original_price' => $voucherPreview['original_amount'],
                    'discount_amount' => $voucherPreview['discount_amount'],
                    'paid_amount'    => $voucherPreview['final_amount'],
                ]);

                if ($voucherCode) {
                    $voucherResult = $this->voucherService->redeem($voucherCode, $user, $planId, $duration, $basePrice, $vps->id);
                    $totalPrice = (int) $voucherResult['final_amount'];

                    if ((int) $user->balance < $totalPrice) {
                        throw new \RuntimeException('INSUFFICIENT_BALANCE');
                    }

                    $vps->update([
                        'voucher_code' => $voucherResult['code'],
                        'original_price' => $voucherResult['original_amount'],
                        'discount_amount' => $voucherResult['discount_amount'],
                        'paid_amount' => $voucherResult['final_amount'],
                    ]);
                }

                $user->decrement('balance', $totalPrice);

                return $vps;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                return back()->with('error', 'So du khong du. Vui long nap them tien de mua VPS.');
            }

            throw $e;
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        try {
            $createdZone = null;
            $lastCreateError = null;

            foreach ($this->fallbackZonesFor($zone) as $tryZone) {
                $vps->update([
                    'zone' => $tryZone,
                    'status' => 'Dang khoi tao...',
                ]);

                try {
                    $this->gcp->createInstance(
                        $vpsName,
                        $tryZone,
                        $plan['type'],
                        $randomPassword,
                        $osType,
                        $plan['disk'],
                        $request->os_image,
                        ['cloudvps-managed', GcpVpsService::firewallTargetTag($vps)]
                    );

                    if (!$this->gcp->waitForInstanceVisibility($vpsName, $tryZone)) {
                        throw new \RuntimeException('GCP instance is not visible after create operation.');
                    }

                    $createdZone = $tryZone;
                    break;
                } catch (\Throwable $createError) {
                    $lastCreateError = $createError;
                    $this->gcp->deleteInstance($vpsName, $tryZone);

                    Log::warning('Create VPS zone attempt failed', [
                        'user_id' => $vps->user_id,
                        'actor_user_id' => Auth::id(),
                        'project_id' => $gcpProject->project_id,
                        'zone' => $tryZone,
                        'machine_type' => $plan['type'],
                        'name' => $vpsName,
                        'message' => $createError->getMessage(),
                    ]);

                    if (!$this->isRetryableCreateError($createError)) {
                        throw $createError;
                    }
                }
            }

            if (!$createdZone) {
                throw $lastCreateError ?: new \RuntimeException('Could not create VPS in any fallback zone.');
            }

            $this->gcp->syncInstanceStatus($vps);
            $this->createDefaultRemoteFirewallRule($vps, $osType);

            $redirectRoute = (int) $vps->user_id === Auth::id()
                ? route('vps.dashboard')
                : route('vps.show', $vps->id);

            return redirect($redirectRoute)->with('success', "Đã tạo máy chủ {$vpsName}. Trạng thái và IP đang được cập nhật.");

        } catch (\Google\ApiCore\ApiException $e) {
            $this->gcp->deleteInstance($vpsName, $zone);

            // Refund balance and clean up VPS record atomically
            $this->refundFailedVpsPurchase($vps, $totalPrice);

            if (str_contains($e->getMessage(), 'QUOTA_EXCEEDED')) {
                Log::warning('Create VPS quota exceeded from provider', [
                    'user_id' => $vps->user_id,
                    'actor_user_id' => Auth::id(),
                    'message' => $e->getMessage(),
                ]);
                return back()->with('error', 'Tạm hết hàng. Vui lòng quay lại sau.');
            }

            Log::error('Create VPS provider API error', [
                'user_id' => $vps->user_id,
                'actor_user_id' => Auth::id(),
                'project_id' => $gcpProject->project_id,
                'zone' => $zone,
                'machine_type' => $plan['type'],
                'name' => $vpsName,
                'message' => $e->getMessage(),
            ]);
            return back()->with('error', 'Tạm hết hàng. Vui lòng quay lại sau.');

        } catch (\Exception $e) {
            $this->gcp->deleteInstance($vpsName, $zone);

            // Refund balance and clean up VPS record atomically
            $this->refundFailedVpsPurchase($vps, $totalPrice);

            Log::error('Create VPS system error', [
                'user_id' => $vps->user_id,
                'actor_user_id' => Auth::id(),
                'project_id' => $gcpProject->project_id,
                'zone' => $zone,
                'machine_type' => $plan['type'],
                'name' => $vpsName,
                'message' => $e->getMessage(),
            ]);
            return back()->with('error', 'Tạm hết hàng. Vui lòng quay lại sau.');
        }
    }

    private function refundFailedVpsPurchase(VpsInstance $vps, int $amount): void
    {
        DB::transaction(function () use ($vps, $amount) {
            \App\Models\User::whereKey($vps->user_id ?: Auth::id())->increment('balance', $amount);

            $redemptions = $vps->voucherRedemptions()->with('voucher')->get();
            foreach ($redemptions as $redemption) {
                if ($redemption->voucher && $redemption->voucher->used_count > 0) {
                    $redemption->voucher->decrement('used_count');
                }
            }

            $vps->voucherRedemptions()->delete();
            $vps->delete();
        });
    }

    public function show($id)
    {
        $vps = $this->findManageableInstance($id);

        // Always sync status when customer views the detail page
        if ($vps->gcpProject && $vps->status !== 'Lỗi API') {
            $this->gcp->setProjectSettings($vps->gcpProject->project_id, $vps->gcpProject->credentials_path);
            $this->gcp->syncInstanceStatus($vps);
        }

        $renewPlan      = $this->pricingService->findPlanByMachineType($vps->machine_type);
        $renewBasePrice = $renewPlan['price_per_month'] ?? $renewPlan['price_per_day'] ?? 0;

        // Pre-compute renewal option prices so the view doesn't need @php math
        $renewOptions = collect([1, 7, 30, 90, 180, 365])->mapWithKeys(
            function ($days) use ($renewBasePrice) {
                return [$days => $this->pricingService->calculatePrice(['price_per_month' => $renewBasePrice], $days)];
            }
        );

        $firewallRules = $vps->firewallRules()
            ->latest()
            ->get();

        return view('vps.show', compact('vps', 'renewBasePrice', 'renewOptions', 'firewallRules'));
    }

    /**
     * Return VPS credentials as JSON for the authenticated user (AJAX).
     */
    public function credentials($id): JsonResponse
    {
        $vps = $this->findManageableInstance($id);
        
        try {
            // Accessing $vps->password triggers the 'encrypted' cast decryption.
            $password = $vps->password;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // If decryption fails, it's likely a legacy plaintext password.
            // We fetch the raw column value from the database instead.
            $password = $vps->getRawOriginal('password');
        }

        return response()->json(['password' => $password ?? '']);
    }

    public function reboot($id)
    {
        $vps = $this->findManageableInstance($id);
        try {
            $this->gcp->setProjectSettings($vps->gcpProject->project_id, $vps->gcpProject->credentials_path);
            $this->gcp->syncInstanceStatus($vps);

            if ($vps->status === 'Đã tắt' || $vps->status === 'TERMINATED') {
                $this->gcp->startInstance($vps->name, $vps->zone);
                $vps->status = 'Khởi động lại...';
                $vps->save();
                return back()->with('success', 'VPS đang Tắt nguồn. Đã dội hệ thống để BẬT NGUỒN máy chủ. Bạn chờ tí cho đèn sáng nhé!');
            } else {
                $this->gcp->rebootInstance($vps->name, $vps->zone);
                $vps->status = 'Khởi động lại...';
                $vps->save();
                return back()->with('success', 'Đã nạp lệnh khởi động cứng (Reboot) vào hệ thống đám mây. VPS sẽ chớp lại màn hình sau 30 giây.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi thực thi: ' . $e->getMessage());
        }
    }

    public function upgrade(Request $request, $id)
    {
        return back()->with('error', 'Tính năng nâng cấp hệ thống đang được bảo trì. Vui lòng quay lại sau.');
    }

    public function updatePassword(Request $request, $id)
    {
        $vps = $this->findManageableInstance($id);

        $isWindows = ($vps->os ?? '') === 'windows';

        if (!$isWindows) {
            $request->validate(['new_password' => 'required|string|min:8|max:32']);
        }

        $newPassword = $request->new_password;

        try {
            $this->gcp->setProjectSettings($vps->gcpProject->project_id, $vps->gcpProject->credentials_path);

            if ($isWindows) {
                $newPassword = $this->gcp->resetWindowsPassword($vps->name, $vps->zone);
            } else {
                $newPassword = $this->gcp->setInstancePassword($vps->name, $vps->zone, $newPassword, $vps->os ?? 'ubuntu');
            }

            $vps->password = $newPassword;
            $vps->save();

            if ($isWindows) {
                return back()->with('success', 'Mật khẩu Windows đã được tạo mới và lưu vào Hệ thống. Quá trình cập nhật có thể mất 1-2 phút để áp dụng.');
            }

            return back()->with('success', 'Mật khẩu đã được đổi thành công và lưu vào Hệ thống. Quá trình reboot sẽ mất vài chục giây để máy chủ áp dụng cấu hình mới.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi đổi mật khẩu: ' . $e->getMessage());
        }
    }

    public function renew(Request $request, $id)
    {
        $vps = $this->findManageableInstance($id);

        $request->validate(['days' => 'required|integer|in:1,7,30,90,180,365']);

        $days = (int) $request->days;
        $plan = $this->pricingService->findPlanByMachineType($vps->machine_type);

        if (!$plan) {
            return back()->with('error', 'Khong xac dinh duoc gia gia han cho goi VPS nay.');
        }

        $totalPrice = $this->pricingService->calculatePrice($plan, $days);

        try {
            DB::transaction(function () use ($vps, $days, $totalPrice) {
                $user = \App\Models\User::whereKey($vps->user_id)->lockForUpdate()->firstOrFail();

                if ((int) $user->balance < $totalPrice) {
                    throw new \RuntimeException('INSUFFICIENT_BALANCE');
                }

                $user->decrement('balance', $totalPrice);

                $lockedVps = VpsInstance::whereKey($vps->id)->lockForUpdate()->firstOrFail();
                $baseDate  = ($lockedVps->expires_at && $lockedVps->expires_at->isFuture())
                    ? $lockedVps->expires_at
                    : now('Asia/Ho_Chi_Minh');

                $lockedVps->expires_at = $baseDate->copy()->addDays($days);
                $lockedVps->save();
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                return back()->with('error', 'So du khong du. Vui long nap them tien de gia han VPS.');
            }

            throw $e;
        }

        return back()->with('success', 'Gia han thanh cong them ' . $days . ' ngay. Da tru ' . number_format($totalPrice) . ' VND.');
    }



    public function openFirewallPort(Request $request, $id)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $vps = VpsInstance::with('gcpProject')->findOrFail($id);

        if (!$vps->gcpProject) {
            return back()->with('error', 'VPS chua gan project Google Cloud nen chua the mo port.');
        }

        $data = $request->validate([
            'protocol' => 'required|in:tcp,udp',
            'port' => 'required|string|max:11',
            'source_type' => 'required|in:any,my_ip,custom',
            'source_range' => 'nullable|string|max:64',
        ]);

        try {
            [$portStart, $portEnd] = VpsFirewallPolicy::parsePortRange($data['port']);
            $sourceRange = VpsFirewallPolicy::normalizeSource($request, $data['source_type'], $data['source_range'] ?? null);
            if ($vps->public_ip && $sourceRange === $vps->public_ip . '/32') {
                throw new \InvalidArgumentException('IP/CIDR nguon dang la IP cua VPS. Hay nhap IP public cua may ban dang Remote Desktop, khong phai IP VPS.');
            }

            VpsFirewallPolicy::assertFirewallPortAllowed($data['protocol'], $portStart, $portEnd, $sourceRange);
            VpsFirewallPolicy::assertVpsPortLimit($vps, $portStart, $portEnd);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $overlaps = VpsFirewallRule::where('vps_instance_id', $vps->id)
            ->where('protocol', $data['protocol'])
            ->where('source_range', $sourceRange)
            ->where('port_start', '<=', $portEnd)
            ->where('port_end', '>=', $portStart)
            ->exists();

        if ($overlaps) {
            return back()->with('error', 'Port hoac range nay da ton tai/bi trung voi rule hien co.');
        }

        $targetTag = GcpVpsService::firewallTargetTag($vps);
        $entryRuleName = VpsFirewallPolicy::entryRuleName($vps, $data['protocol'], $portStart, $portEnd, $sourceRange);

        try {
            VpsFirewallRule::create([
                'user_id' => $vps->user_id,
                'vps_instance_id' => $vps->id,
                'gcp_project_id' => $vps->gcp_project_id,
                'rule_name' => $entryRuleName,
                'target_tag' => $targetTag,
                'protocol' => $data['protocol'],
                'port_start' => $portStart,
                'port_end' => $portEnd,
                'source_range' => $sourceRange,
                'sync_status' => 'pending',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Port nay da duoc mo cho VPS.');
        }

        Log::info('User requested VPS firewall open', [
            'user_id' => Auth::id(),
            'vps_id' => $vps->id,
            'protocol' => $data['protocol'],
            'port_start' => $portStart,
            'port_end' => $portEnd,
            'source_range' => $sourceRange,
            'contains_blocked_port' => VpsFirewallPolicy::containsBlockedPort($portStart, $portEnd),
            'contains_restricted_port' => VpsFirewallPolicy::containsRestrictedPort($portStart, $portEnd),
        ]);

        if (!VpsFirewallPolicy::isPublicSource($sourceRange) && VpsFirewallPolicy::containsBlockedPort($portStart, $portEnd)) {
            Log::warning('Restricted sensitive port opened with non-public source', [
                'user_id' => Auth::id(),
                'vps_id' => $vps->id,
                'protocol' => $data['protocol'],
                'port_start' => $portStart,
                'port_end' => $portEnd,
                'source_range' => $sourceRange,
            ]);
        }

        SyncVpsFirewallRules::dispatch($vps->id);

        return back()->with('success', 'Da luu yeu cau mo port. Firewall dang duoc dong bo len Google Cloud.');
    }

    public function deleteFirewallRule($id, $rule)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $vps = VpsInstance::findOrFail($id);
        $firewallRule = $vps->firewallRules()->whereKey($rule)->firstOrFail();

        $staleRuleNames = [
            $firewallRule->rule_name,
            VpsFirewallPolicy::groupRuleName($vps, $firewallRule->protocol, $firewallRule->source_range),
        ];

        $label = strtoupper($firewallRule->protocol) . ' ' . $firewallRule->portLabel();
        $sourceRange = $firewallRule->source_range;
        $firewallRule->delete();

        Log::info('User requested VPS firewall close', [
            'user_id' => Auth::id(),
            'vps_id' => $vps->id,
            'label' => $label,
            'source_range' => $sourceRange,
        ]);

        SyncVpsFirewallRules::dispatch($vps->id, $staleRuleNames);

        return back()->with('success', 'Da luu yeu cau dong port ' . $label . '. Firewall dang duoc dong bo len Google Cloud.');
    }

    public function destroy($id)
    {
        $vps = $this->findManageableInstance($id);

        try {
            if ($vps->gcpProject) {
                $this->gcp->setProjectSettings($vps->gcpProject->project_id, $vps->gcpProject->credentials_file);

                $ruleNames = $vps->firewallRules
                    ->flatMap(function ($rule) use ($vps) {
                        return [
                            $rule->rule_name,
                            VpsFirewallPolicy::groupRuleName($vps, $rule->protocol, $rule->source_range),
                        ];
                    })
                    ->filter()
                    ->unique();

                CleanupVpsFirewallRules::dispatch(
                    $vps->gcpProject->project_id,
                    $vps->gcpProject->credentials_file,
                    $ruleNames->values()->all()
                );

                $this->gcp->deleteInstance($vps->name, $vps->zone);
            }
            $vps->delete();
            return redirect()->route('vps.dashboard')->with('success', "Máy chủ {$vps->name} đã được đưa vào luồng xóa thành công.");
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xóa máy chủ: ' . $e->getMessage());
        }
    }
}
