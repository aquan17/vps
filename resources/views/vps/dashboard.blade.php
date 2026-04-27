@extends('layouts.app')

@section('title', 'Tổng quan VPS - CloudVPS')
@section('meta_description', 'Tổng quan VPS CloudVPS, trạng thái máy chủ, IP công khai và thao tác quản lý nhanh.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $instances = $instances ?? collect();
    $runningCount = $instances->whereIn('status', ['Sẵn sàng', 'Đang chạy', 'RUNNING'])->count();
@endphp

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Tổng quan VPS</h1>
        <p class="text-secondary mb-0">Theo dõi trạng thái máy chủ và thao tác nhanh.</p>
    </div>
    <a href="{{ route('vps.create') }}" class="btn btn-primary fw-semibold">+ Tạo VPS mới</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small fw-semibold mb-2">Tổng VPS</div>
                <div class="display-6 fw-bold">{{ $instances->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small fw-semibold mb-2">Đang chạy</div>
                <div class="display-6 fw-bold text-success">{{ $runningCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-uppercase text-secondary small fw-semibold mb-2">Sẵn sàng tạo mới</div>
                <div class="display-6 fw-bold text-primary">24/7</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h2 class="h5 fw-bold mb-0">Máy chủ của bạn</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên VPS</th>
                    <th>Cấu hình</th>
                    <th>IP</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($instances as $vps)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $vps->name }}</div>
                            <div class="text-secondary small">{{ $vps->zone }}</div>
                        </td>
                        <td class="font-monospace small">{{ $vps->machine_type }}</td>
                        <td class="font-monospace small">{{ $vps->public_ip ?? 'Đang chờ IP...' }}</td>
                        <td>
                            <span class="badge rounded-pill {{ in_array($vps->status, ['Sẵn sàng', 'Đang chạy', 'RUNNING']) ? 'text-bg-success' : 'text-bg-warning' }}">
                                {{ $vps->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('vps.show', $vps->id) }}" class="btn btn-sm btn-primary fw-semibold">Quản lý</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-5">Chưa có VPS nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
