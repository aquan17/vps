@extends('layouts.app')

@section('title', 'Quản trị GCP Accounts - CloudVPS')
@section('meta_description', 'Theo dõi tài khoản Google Cloud, quota CPU RAM và số lượng VPS đang chạy trong hệ thống CloudVPS.')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0"><i class="fas fa-server text-primary"></i> G GCP Accounts</h2>
    </div>
    <div class="col-md-6 text-end">
        <form action="{{ route('admin.gcloud.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary fw-bold">
                <i class="fas fa-sync-alt"></i> Đồng bộ
            </button>
        </form>
        <button class="btn btn-primary fw-bold ms-2" data-bs-toggle="modal" data-bs-target="#addAccountModal">
            <i class="fas fa-plus"></i> Thêm Account
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row text-center text-md-start">
            <div class="col-md-3 border-end">
                <div class="text-success fw-bold">
                    <i class="fas fa-check-circle"></i> Active: {{ $activeCount }}
                </div>
                <div class="text-secondary mt-3">
                    <i class="fas fa-pause-circle"></i> Suspended: {{ $suspendedCount }}
                </div>
                <div class="text-dark fw-bold mt-3">
                    <i class="fas fa-server"></i> Instances: {{ $totalInstances }}/{{ $maxTotalInstances }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-warning fw-bold">
                    <i class="fas fa-exclamation-circle"></i> Full: {{ $fullCount }}
                </div>
                <div class="text-dark fw-bold mt-3">
                    <i class="fas fa-microchip"></i> CPU: {{ $totalCpuUsage }}/{{ $totalCpuLimit }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted" style="font-size: 0.85rem;">
                    <tr>
                        <th class="ps-4">TÊN / PROJECT ID</th>
                        <th>ZONE MẶC ĐỊNH</th>
                        <th>CPU DÙNG/MAX</th>
                        <th>RAM DÙNG/MAX</th>
                        <th>INSTANCES</th>
                        <th class="text-center">VPS ĐANG CHẠY</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $pj)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ explode('@', json_decode(file_get_contents($pj->credentials_file), true)['client_email'] ?? '')[0] ?? 'N/A' }}</div>
                            <div class="text-muted small">{{ $pj->project_id }}</div>
                        </td>
                        <td>
                            <span class="text-muted small">asia-southeast1-a</span>
                        </td>
                        <td>
                            @php
                                $cpuPct = $pj->cpu_limit > 0 ? round(($pj->cpu_usage / $pj->cpu_limit) * 100) : 0;
                                $cpuColor = $cpuPct > 80 ? 'bg-danger' : ($cpuPct > 50 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>{{ $pj->cpu_usage }}/{{ $pj->cpu_limit }} vCPU ({{ $cpuPct }}%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $cpuColor }}" style="width: {{ $cpuPct }}%"></div>
                            </div>
                        </td>
                        <td>
                            @php
                                $ramPct = $pj->ram_limit > 0 ? round(($pj->ram_usage / $pj->ram_limit) * 100) : 0;
                                $ramColor = $ramPct > 80 ? 'bg-danger' : ($ramPct > 50 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>{{ $pj->ram_usage }}/{{ $pj->ram_limit }} GB ({{ $ramPct }}%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $ramColor }}" style="width: {{ $ramPct }}%"></div>
                            </div>
                        </td>
                        <td>
                            @php
                                $instPct = $pj->inst_limit > 0 ? round(($pj->current_instances / $pj->inst_limit) * 100) : 0;
                                $instColor = $instPct > 80 ? 'bg-danger' : ($instPct > 50 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>{{ $pj->current_instances }}/{{ $pj->inst_limit }} ({{ $instPct }}%)</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar {{ $instColor }}" style="width: {{ $instPct }}%"></div>
                            </div>
                        </td>
                        <td class="text-center fw-bold">{{ $pj->running_vps }}</td>
                    </tr>
                    @endforeach
                    @if($projects->isEmpty())
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Chưa có Account Cloud nào.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Account -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.gcloud.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Thêm Account GCP Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <div class="mb-3">
                        <label for="jsonFile" class="form-label text-muted d-block fw-bold">TẢI LÊN FILE SERVICE ACCOUNT (.JSON)</label>
                        <input class="form-control mt-2" type="file" id="jsonFile" name="credentials" accept=".json" required>
                    </div>
                    <div class="alert alert-info border-0 text-start mt-4 mb-0" style="background-color: rgba(59,130,246,0.1);">
                        <i class="fas fa-info-circle"></i> Hệ thống sẽ tự kiểm tra API Key. Nếu chính xác sẽ thêm vào cụm cân bằng tải.
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary fw-bold" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Đang test API..';">Add Account</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
