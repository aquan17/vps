@extends('layouts.app')

@section('title', 'Dashboard VPS - CloudVPS')
@section('meta_description', 'Bảng điều khiển CloudVPS giúp theo dõi VPS, IP, cấu hình, trạng thái và thao tác quản lý máy chủ.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $runningCount = $instances->whereIn('status', ['Đang chạy', 'RUNNING'])->count();
    $pendingCount = max(0, $instances->count() - $runningCount);
@endphp

<x-ui.page-header
    title="Danh sách VPS"
    subtitle="Quản lý máy chủ, IP, trạng thái và thao tác nhanh."
>
    <x-ui.button :href="route('vps.create')" variant="primary">
        + Tạo VPS mới
    </x-ui.button>
</x-ui.page-header>

<div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <x-ui.stat-card class="vps-stat-card" label="Máy chủ của tôi" :value="$instances->count()" subtitle="Tổng VPS đang thuê" />
    <x-ui.stat-card class="vps-stat-card" label="Đang hoạt động" :value="$runningCount" subtitle="Online" variant="success" />
    <x-ui.stat-card class="vps-stat-card" label="Chờ xử lý" :value="$pendingCount" subtitle="Đang khởi tạo hoặc kiểm tra" variant="warning" />
</div>

@if($instances->count() > 0)
    <div class="grid gap-4 lg:hidden">
        @foreach($instances as $instance)
            <x-vps.instance-card :instance="$instance" />
        @endforeach
    </div>
    
    <x-ui.card class="vps-index-card hidden overflow-hidden lg:block" padding="none">
        <x-slot name="header">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="mb-0.5 text-base font-bold text-slate-950">Tài nguyên đang hoạt động</h2>
                    <p class="mb-0 text-xs text-slate-500">{{ $instances->count() }} máy chủ trong tài khoản của bạn</p>
                </div>
                <x-ui.badge variant="success">{{ $runningCount }} online</x-ui.badge>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="vps-index-table min-w-full border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Hostname</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Gói / Khu vực</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Địa chỉ IP</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Trạng thái</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Hết hạn</th>
                        <th class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($instances as $instance)
                        @php
                            $expiresAt = $instance->expires_at
                                ? $instance->expires_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                                : 'Chưa có hạn';
                        @endphp
                        <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-950 text-sm">{{ $instance->name }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $instance->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs font-bold text-brand-700">{{ strtoupper($instance->machine_type) }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $instance->zone }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-semibold text-slate-700">{{ $instance->public_ip ?? 'Đang khởi tạo...' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :status="$instance->status" size="sm">{{ $instance->status }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                <span class="vps-expiry-text text-sm font-semibold text-danger-700">{{ $expiresAt }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <x-ui.button :href="route('vps.show', $instance->id)" size="sm">Quản lý</x-ui.button>
                                    <form action="{{ route('vps.destroy', $instance->id) }}" method="POST" class="m-0" onsubmit="return confirm('Xóa VPS ' + @json($instance->name) + '?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Xóa</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@else
    <x-ui.card class="vps-index-empty text-center" padding="lg">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-2xl text-brand-700">VPS</div>
        <h2 class="mb-2 text-xl font-bold text-slate-950">Bạn chưa có VPS nào</h2>
        <p class="mx-auto mb-5 max-w-md text-sm text-slate-500">Khởi tạo máy chủ đầu tiên để bắt đầu vận hành dịch vụ.</p>
        <x-ui.button :href="route('vps.create')" size="lg">+ Tạo VPS ngay</x-ui.button>
    </x-ui.card>
@endif
@endsection

@push('styles')
<style>
    .vps-stat-card {
        min-height: 126px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding-top: 16px;
        padding-bottom: 16px;
    }

    .vps-stat-card > .flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .vps-stat-card > .flex > div:first-child {
        font-size: 11px;
        line-height: 1rem;
        letter-spacing: .06em;
    }

    .vps-stat-card > .flex > div:last-child {
        font-size: 32px;
        line-height: 1;
    }

    .vps-stat-card > div:nth-child(2) {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        border-width: 1px;
        border-radius: 9999px;
        padding: 6px 10px;
        font-size: 11px;
        line-height: 1rem;
    }

    .vps-index-card > .border-b {
        padding-top: 16px;
        padding-bottom: 16px;
    }

    .vps-index-table th {
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .vps-index-table td {
        padding-top: 16px;
        padding-bottom: 16px;
    }

    .vps-index-table tbody td,
    .vps-index-table tbody td .text-sm {
        font-size: 14px;
        line-height: 1.25rem;
    }

    .vps-index-table tbody td .text-xs {
        font-size: 13px;
        line-height: 1.125rem;
    }

    .vps-expiry-text {
        white-space: nowrap;
    }

    .vps-index-empty > div {
        padding-top: 28px;
        padding-bottom: 28px;
    }

    @media (max-width: 768px) {
        .vps-stat-card {
            padding-top: 14px;
            padding-bottom: 14px;
        }
    }
</style>
@endpush
