@extends('layouts.app')

@section('title', 'Quản trị Google Cloud - CloudVPS')
@section('meta_description', 'Quản trị tài khoản Google Cloud, quota, project và toàn bộ VPS trong hệ thống CloudVPS.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $projectCount = count($projects);
    $inactiveCount = collect($projects)->where('is_active', false)->count();
    $cpuPercent = $stats['cpu_limit'] > 0 ? min(100, round(($stats['cpu_used'] / $stats['cpu_limit']) * 100)) : 0;
    $instancePercent = $stats['inst_limit'] > 0 ? min(100, round(($stats['instances'] / $stats['inst_limit']) * 100)) : 0;
    $quotaNumber = function ($value) {
        $value = (float) $value;
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', '.')
            : number_format($value, 1, ',', '.');
    };
    $quotaTone = function ($percent) {
        return $percent >= 90 ? 'danger' : ($percent >= 70 ? 'warning' : 'success');
    };
@endphp

<x-ui.page-header
    eyebrow="Quản trị"
    title="Google Cloud"
    subtitle="Theo dõi project, quota và VPS đang chạy trong cụm cấp phát tự động."
>
    <form action="{{ route('admin.gcloud.sync') }}" method="POST" class="m-0">
        @csrf
        <x-ui.button type="submit" variant="secondary">Đồng bộ quota</x-ui.button>
    </form>
    <form action="{{ route('admin.gcloud.sync-vps') }}" method="POST" class="m-0">
        @csrf
        <x-ui.button type="submit" variant="secondary">Đồng bộ VPS</x-ui.button>
    </form>
    <x-ui.button type="button" variant="primary" data-bs-toggle="modal" data-bs-target="#addGcpModal">
        + Thêm account
    </x-ui.button>
</x-ui.page-header>

<div class="admin-gcloud-page space-y-4">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            class="gcloud-stat-card gcloud-stat-card--success"
            label="Project khả dụng"
            :value="$stats['active']"
            variant="success"
            :subtitle="$projectCount . ' project trong cụm'"
        />
        <x-ui.stat-card
            class="gcloud-stat-card gcloud-stat-card--warning"
            label="Đã đầy"
            :value="$stats['full']"
            variant="warning"
            :subtitle="$inactiveCount . ' project đang tắt'"
        />
        <x-ui.stat-card
            class="gcloud-stat-card gcloud-stat-card--primary"
            label="CPU quota"
            :value="$quotaNumber($stats['cpu_used']) . '/' . $quotaNumber($stats['cpu_limit'])"
            :variant="$quotaTone($cpuPercent)"
            :subtitle="$cpuPercent . '% đã sử dụng'"
        />
        <x-ui.stat-card
            class="gcloud-stat-card gcloud-stat-card--info"
            label="Instances"
            :value="$quotaNumber($stats['instances']) . '/' . $quotaNumber($stats['inst_limit'])"
            :variant="$quotaTone($instancePercent)"
            :subtitle="$instancePercent . '% sức chứa'"
        />
    </div>

    <x-ui.card class="admin-gcloud-card overflow-hidden" padding="none">
        <div class="gcloud-cluster-grid">
            <div class="gcloud-cluster-block">
                <div class="gcloud-cluster-label">CPU toàn cụm</div>
                <div class="gcloud-cluster-value">{{ $quotaNumber($stats['cpu_used']) }} / {{ $quotaNumber($stats['cpu_limit']) }} vCPU</div>
                <div class="gcloud-progress gcloud-progress--{{ $quotaTone($cpuPercent) }}">
                    <span style="width: {{ $cpuPercent }}%;"></span>
                </div>
            </div>
            <div class="gcloud-cluster-block">
                <div class="gcloud-cluster-label">Instance toàn cụm</div>
                <div class="gcloud-cluster-value">{{ $quotaNumber($stats['instances']) }} / {{ $quotaNumber($stats['inst_limit']) }} máy chủ</div>
                <div class="gcloud-progress gcloud-progress--{{ $quotaTone($instancePercent) }}">
                    <span style="width: {{ $instancePercent }}%;"></span>
                </div>
            </div>
            <div class="gcloud-cluster-mini">
                <div><strong>{{ $stats['active'] }}</strong><span>khả dụng</span></div>
                <div><strong>{{ $stats['full'] }}</strong><span>đã đầy</span></div>
                <div><strong>{{ $inactiveCount }}</strong><span>đang tắt</span></div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="admin-gcloud-card overflow-hidden" padding="none">
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h2 class="mb-0.5 text-base font-bold text-slate-950">Google Cloud projects</h2>
                    <p class="mb-0 text-xs text-slate-500">{{ $projectCount }} account trong cụm cấp phát.</p>
                </div>
                <x-ui.badge variant="primary" class="self-start sm:self-auto">{{ $projectCount }} projects</x-ui.badge>
            </div>
        </x-slot>

        <div class="gcloud-project-scroll grid gap-3 p-4 lg:hidden">
            @forelse($projects as $pj)
                @php
                    $cpuPct = $pj['cpu_limit'] > 0 ? min(100, round(($pj['cpu_used'] / $pj['cpu_limit']) * 100)) : 0;
                    $ramPct = $pj['ram_limit'] > 0 ? min(100, round(($pj['ram_used'] / $pj['ram_limit']) * 100)) : 0;
                    $instPct = $pj['inst_limit'] > 0 ? min(100, round(($pj['vps_count'] / $pj['inst_limit']) * 100)) : 0;
                @endphp
                <article class="gcloud-project-card">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slate-950">{{ $pj['name'] }}</div>
                            <div class="mt-1 truncate font-mono text-xs text-slate-500">{{ $pj['project_id'] }}</div>
                        </div>
                        @if(!$pj['is_active'])
                            <x-ui.badge variant="danger">Tắt</x-ui.badge>
                        @elseif($pj['is_full'])
                            <x-ui.badge variant="warning">Full</x-ui.badge>
                        @else
                            <x-ui.badge variant="success">Ready</x-ui.badge>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="gcloud-meter-head"><span>CPU</span><strong>{{ $quotaNumber($pj['cpu_used']) }}/{{ $quotaNumber($pj['cpu_limit']) }} vCPU</strong></div>
                            <div class="gcloud-progress gcloud-progress--{{ $quotaTone($cpuPct) }}"><span style="width: {{ $cpuPct }}%;"></span></div>
                        </div>
                        <div>
                            <div class="gcloud-meter-head"><span>RAM</span><strong>{{ $quotaNumber($pj['ram_used']) }}/{{ $quotaNumber($pj['ram_limit']) }} GB</strong></div>
                            <div class="gcloud-progress gcloud-progress--{{ $quotaTone($ramPct) }}"><span style="width: {{ $ramPct }}%;"></span></div>
                        </div>
                        <div>
                            <div class="gcloud-meter-head"><span>Instances</span><strong>{{ $quotaNumber($pj['vps_count']) }}/{{ $quotaNumber($pj['inst_limit']) }}</strong></div>
                            <div class="gcloud-progress gcloud-progress--{{ $quotaTone($instPct) }}"><span style="width: {{ $instPct }}%;"></span></div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <form action="{{ route('admin.gcloud.toggle', $pj['id']) }}" method="POST" class="m-0">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="field" value="is_full">
                            <x-ui.button type="submit" variant="secondary" size="sm" full-width>
                                {{ $pj['is_full'] ? 'Mở lại' : 'Đánh dấu full' }}
                            </x-ui.button>
                        </form>
                        <form action="{{ route('admin.gcloud.toggle', $pj['id']) }}" method="POST" class="m-0">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="field" value="is_active">
                            <x-ui.button type="submit" :variant="$pj['is_active'] ? 'danger' : 'primary'" size="sm" full-width>
                                {{ $pj['is_active'] ? 'Tắt' : 'Bật' }}
                            </x-ui.button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="gcloud-empty-state">Chưa có account GCP nào.</div>
            @endforelse
        </div>

        <div class="gcloud-project-scroll hidden w-full overflow-x-auto lg:block">
            <table class="gcloud-table min-w-full border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">CPU quota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RAM quota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Instances</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $pj)
                        @php
                            $cpuPct = $pj['cpu_limit'] > 0 ? min(100, round(($pj['cpu_used'] / $pj['cpu_limit']) * 100)) : 0;
                            $ramPct = $pj['ram_limit'] > 0 ? min(100, round(($pj['ram_used'] / $pj['ram_limit']) * 100)) : 0;
                            $instPct = $pj['inst_limit'] > 0 ? min(100, round(($pj['vps_count'] / $pj['inst_limit']) * 100)) : 0;
                        @endphp
                        <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="text-sm font-bold text-slate-950">{{ $pj['name'] }}</div>
                                <div class="mt-1 font-mono text-xs text-slate-500">{{ $pj['project_id'] }}</div>
                            </td>
                            <td class="px-4 py-4" style="min-width:170px;">
                                <div class="gcloud-meter-head"><span>CPU</span><strong>{{ $quotaNumber($pj['cpu_used']) }}/{{ $quotaNumber($pj['cpu_limit']) }}</strong></div>
                                <div class="gcloud-progress gcloud-progress--{{ $quotaTone($cpuPct) }}"><span style="width: {{ $cpuPct }}%;"></span></div>
                            </td>
                            <td class="px-4 py-4" style="min-width:170px;">
                                <div class="gcloud-meter-head"><span>RAM</span><strong>{{ $quotaNumber($pj['ram_used']) }}/{{ $quotaNumber($pj['ram_limit']) }} GB</strong></div>
                                <div class="gcloud-progress gcloud-progress--{{ $quotaTone($ramPct) }}"><span style="width: {{ $ramPct }}%;"></span></div>
                            </td>
                            <td class="px-4 py-4" style="min-width:170px;">
                                <div class="gcloud-meter-head"><span>VPS</span><strong>{{ $quotaNumber($pj['vps_count']) }}/{{ $quotaNumber($pj['inst_limit']) }}</strong></div>
                                <div class="gcloud-progress gcloud-progress--{{ $quotaTone($instPct) }}"><span style="width: {{ $instPct }}%;"></span></div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if(!$pj['is_active'])
                                    <x-ui.badge variant="danger">Ngừng hoạt động</x-ui.badge>
                                @elseif($pj['is_full'])
                                    <x-ui.badge variant="warning">Hết tài nguyên</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">Hoạt động</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <form action="{{ route('admin.gcloud.toggle', $pj['id']) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="field" value="is_full">
                                        <x-ui.button type="submit" variant="secondary" size="sm">
                                            {{ $pj['is_full'] ? 'Mở lại' : 'Full' }}
                                        </x-ui.button>
                                    </form>
                                    <form action="{{ route('admin.gcloud.toggle', $pj['id']) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="field" value="is_active">
                                        <x-ui.button type="submit" :variant="$pj['is_active'] ? 'danger' : 'primary'" size="sm">
                                            {{ $pj['is_active'] ? 'Tắt' : 'Bật' }}
                                        </x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                Chưa có account GCP nào. Nhấn <strong>+ Thêm account</strong> để upload file JSON đầu tiên.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card class="admin-gcloud-card overflow-hidden" padding="none">
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h2 class="mb-0.5 text-base font-bold text-slate-950">VPS trong hệ thống</h2>
                    <p class="mb-0 text-xs text-slate-500">{{ $allInstances->total() }} máy chủ đang được quản lý.</p>
                </div>
                <x-ui.badge variant="primary" class="self-start sm:self-auto">{{ $allInstances->total() }} instances</x-ui.badge>
            </div>
        </x-slot>

        <div class="grid gap-3 p-4 lg:hidden">
            @forelse($allInstances as $instance)
                @php
                    $expiresDate = $instance->expires_at
                        ? $instance->expires_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d')
                        : '';
                @endphp
                <article class="gcloud-instance-card">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slate-950">{{ $instance->name }}</div>
                            <div class="mt-1 truncate text-xs text-slate-500">{{ $instance->user->name ?? 'Unknown' }} · {{ $instance->user->email ?? '' }}</div>
                        </div>
                        <x-ui.badge :status="$instance->status">{{ $instance->status }}</x-ui.badge>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="min-w-0">
                            <span class="gcloud-mobile-label">Cấu hình</span>
                            <strong class="mt-1 block truncate font-mono text-slate-800" title="{{ strtoupper($instance->machine_type) }}">{{ strtoupper($instance->machine_type) }}</strong>
                            <span class="mt-0.5 block truncate text-slate-500">{{ $instance->cpu }}C · {{ $instance->ram }}GB · {{ $instance->disk }}GB</span>
                        </div>
                        <div class="min-w-0 text-right">
                            <span class="gcloud-mobile-label">IP</span>
                            <strong class="mt-1 block truncate font-mono text-slate-800" title="{{ $instance->public_ip ?? 'Pending...' }}">{{ $instance->public_ip ?? 'Pending...' }}</strong>
                            <span class="mt-0.5 block truncate text-slate-500">{{ strtoupper($instance->os ?? 'ubuntu') }}</span>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 pt-3">
                        <form action="{{ route('admin.gcloud.vps.expires-at', $instance->id) }}" method="POST" class="gcloud-expiry-form m-0 w-full sm:w-auto">
                            @csrf
                            @method('PATCH')
                            <span class="gcloud-expiry-label">Hết hạn</span>
                            <input type="date" name="expires_at" value="{{ $expiresDate }}" class="gcloud-date-input" required>
                            <x-ui.button type="submit" variant="secondary" size="sm">Lưu</x-ui.button>
                        </form>
                        <div class="grid grid-cols-2 gap-2 w-full sm:w-auto sm:flex sm:gap-2">
                            <x-ui.button :href="route('vps.show', $instance->id)" size="sm" class="w-full justify-center">Quản lý</x-ui.button>
                            <form action="{{ route('vps.destroy', $instance->id) }}" method="POST" class="m-0 w-full" onsubmit="return confirm('Xóa VPS ' + @json($instance->name) + '?');">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm" class="w-full justify-center">Xóa</x-ui.button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="gcloud-empty-state">Chưa có VPS nào trong hệ thống.</div>
            @endforelse
        </div>

        <div class="hidden w-full overflow-x-auto lg:block">
            <table class="gcloud-table min-w-full border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Khách hàng</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">VPS</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Cấu hình</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IP / Trạng thái</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Hết hạn</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allInstances as $instance)
                        @php
                            $expiresDate = $instance->expires_at
                                ? $instance->expires_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d')
                                : '';
                        @endphp
                        <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-950">{{ $instance->user->name ?? 'Unknown' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $instance->user->email ?? '' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-950">{{ $instance->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ strtoupper($instance->os ?? 'ubuntu') }} · {{ $instance->zone }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-mono text-sm font-bold text-brand-700">{{ strtoupper($instance->machine_type) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $instance->cpu }}C · {{ $instance->ram }}GB · {{ $instance->disk }}GB</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="mb-1 font-mono text-sm font-semibold text-slate-700">{{ $instance->public_ip ?? 'Pending...' }}</div>
                                <x-ui.badge :status="$instance->status">{{ $instance->status }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-4">
                                <form action="{{ route('admin.gcloud.vps.expires-at', $instance->id) }}" method="POST" class="gcloud-expiry-form m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="date" name="expires_at" value="{{ $expiresDate }}" class="gcloud-date-input" required>
                                    <x-ui.button type="submit" variant="secondary" size="sm">Lưu</x-ui.button>
                                </form>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('vps.show', $instance->id)" size="sm">Quản lý</x-ui.button>
                                    <form action="{{ route('vps.destroy', $instance->id) }}" method="POST" class="m-0" onsubmit="return confirm('Xóa VPS ' + @json($instance->name) + '?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Xóa</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Chưa có VPS nào trong hệ thống.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($allInstances->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $allInstances->links() }}
            </div>
        @endif
    </x-ui.card>
</div>

<div class="modal fade" id="addGcpModal" tabindex="-1" aria-labelledby="addGcpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gcloud-modal overflow-hidden border-0 bg-white shadow-2xl">
            <form action="{{ route('admin.gcloud.store') }}" method="POST" enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="modal-header border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="modal-title text-lg font-bold text-slate-950" id="addGcpModalLabel">Thêm account GCP</h2>
                        <p class="mb-0 mt-1 text-xs text-slate-500">Upload file Service Account JSON để thêm project vào cụm.</p>
                    </div>
                    <button type="button" class="btn-close !m-0 !p-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-5">
                    <div class="mb-4 rounded-lg border border-brand-100 bg-brand-50 p-4 text-sm text-brand-700">
                        Hệ thống sẽ kiểm tra quyền Compute Engine trước khi kích hoạt cân bằng tải.
                    </div>

                    <label for="credentials" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">File Service Account (.json)</label>
                    <input
                        type="file"
                        id="credentials"
                        name="credentials"
                        accept=".json"
                        class="gcloud-file-input block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-brand-700 hover:file:bg-brand-100"
                        required
                    >

                    <div class="mt-4 rounded-lg border border-warning-100 bg-warning-50 p-3 text-xs font-semibold text-warning-700">
                        Chỉ dùng JSON có quyền Editor hoặc Compute Admin phù hợp.
                    </div>
                </div>
                <div class="modal-footer border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" data-bs-dismiss="modal">Hủy</x-ui.button>
                    <x-ui.button type="submit">Thêm account</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .admin-gcloud-page {
        padding-bottom: 4px;
    }

    .gcloud-stat-card {
        min-height: 112px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px;
        position: relative;
        overflow: hidden;
    }

    .gcloud-stat-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 3px;
        background: #3b82f6;
    }

    .gcloud-stat-card--success::before { background: #10b981; }
    .gcloud-stat-card--warning::before { background: #f59e0b; }
    .gcloud-stat-card--info::before { background: #06b6d4; }

    .gcloud-stat-card > .flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .gcloud-stat-card > .flex > div:first-child {
        font-size: 11px;
        line-height: 1rem;
        letter-spacing: .06em;
    }

    .gcloud-stat-card > .flex > div:last-child {
        font-size: 24px;
        line-height: 1.1;
        white-space: normal;
        word-break: break-word;
    }

    .gcloud-stat-card > div:nth-child(2) {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        border-width: 1px;
        border-radius: 9999px;
        padding: 5px 9px;
        font-size: 11px;
        line-height: 1rem;
    }

    .admin-gcloud-card > .border-b {
        padding: 14px 18px;
    }

    .gcloud-cluster-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        padding: 16px 18px;
    }

    .gcloud-cluster-label,
    .gcloud-meter-head span,
    .gcloud-mobile-label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
    }

    .gcloud-cluster-value {
        margin: 4px 0 9px;
        font-family: 'Roboto Mono', monospace;
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .gcloud-cluster-mini {
        display: grid;
        grid-template-columns: repeat(3, minmax(72px, 1fr));
        gap: 8px;
    }

    .gcloud-cluster-mini div {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 10px;
        text-align: center;
    }

    .gcloud-cluster-mini strong,
    .gcloud-cluster-mini span {
        display: block;
    }

    .gcloud-cluster-mini strong {
        font-family: 'Roboto Mono', monospace;
        font-size: 18px;
        line-height: 1;
        color: #0f172a;
    }

    .gcloud-cluster-mini span {
        margin-top: 5px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748b;
    }

    .gcloud-progress {
        height: 8px;
        overflow: hidden;
        border-radius: 9999px;
        background: #e2e8f0;
    }

    .gcloud-progress span {
        display: block;
        height: 100%;
        min-width: 4px;
        border-radius: inherit;
        transition: width .25s ease;
    }

    .gcloud-progress--success span { background: #10b981; }
    .gcloud-progress--warning span { background: #f59e0b; }
    .gcloud-progress--danger span { background: #ef4444; }

    .gcloud-project-card,
    .gcloud-instance-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 14px;
        max-width: 100%;
        overflow: hidden;
    }

    .gcloud-project-scroll {
        max-height: min(620px, 68vh);
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .gcloud-instance-card .truncate {
        max-width: 100%;
    }

    .gcloud-meter-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 7px;
    }

    .gcloud-meter-head strong {
        color: #334155;
        font-family: 'Roboto Mono', monospace;
        font-size: 12px;
        line-height: 1rem;
        white-space: nowrap;
    }

    .gcloud-expiry-form {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .gcloud-expiry-label {
        flex: 0 0 auto;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
    }

    .gcloud-date-input {
        min-height: 36px;
        width: 150px;
        max-width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        padding: 0 10px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }

    .gcloud-table th {
        padding-top: 12px;
        padding-bottom: 12px;
        white-space: nowrap;
    }

    .gcloud-table td {
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .gcloud-empty-state {
        padding: 28px 18px;
        text-align: center;
        color: #64748b;
        font-size: 14px;
    }

    .gcloud-modal {
        border-radius: 12px;
    }

    .gcloud-file-input {
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        padding: 12px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 1024px) {
        .gcloud-cluster-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .gcloud-stat-card {
            min-height: auto;
            padding: 14px;
        }

        .gcloud-stat-card > .flex > div:last-child {
            font-size: 20px;
        }

        .gcloud-cluster-grid {
            padding: 16px;
            gap: 16px;
        }

        .gcloud-cluster-mini {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .gcloud-stat-card > .flex > div:last-child {
            font-size: 18px;
        }
        
        .gcloud-cluster-mini {
            grid-template-columns: 1fr;
            gap: 8px;
        }
        
        .gcloud-cluster-mini div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
        }
        
        .gcloud-cluster-mini span {
            margin-top: 0;
            font-size: 11px;
        }

        .gcloud-instance-card {
            padding: 12px;
        }

        .gcloud-instance-card > .mb-3 {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .gcloud-instance-card > .grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .gcloud-instance-card > .grid > .text-right {
            text-align: left;
        }

        .gcloud-instance-card > .mt-3 {
            align-items: stretch;
        }

        .gcloud-expiry-form {
            width: 100%;
        }

        .gcloud-date-input {
            flex: 1 1 auto;
            width: auto;
            min-width: 0;
        }

        .gcloud-instance-card > .mt-3 > .grid {
            grid-template-columns: 1fr;
            width: 100%;
        }

        .gcloud-instance-card > .mt-3 a,
        .gcloud-instance-card > .mt-3 button {
            width: 100%;
            min-width: 0;
        }
    }
</style>
@endpush
