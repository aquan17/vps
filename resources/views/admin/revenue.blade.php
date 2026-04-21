@extends('layouts.app')

@section('title', 'Thống kê doanh thu - CloudVPS')
@section('meta_description', 'Thống kê doanh thu nạp tiền, giao dịch và khách hàng cho quản trị viên CloudVPS.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $money = function ($value) {
        return number_format((int) $value, 0, ',', '.') . ' VND';
    };
    $monthTrend = $monthChangePercent === null
        ? 'Chưa có dữ liệu tháng trước'
        : ($monthChangePercent >= 0 ? '+' : '') . $monthChangePercent . '% so với tháng trước';
    $monthTrendVariant = $monthChangePercent === null
        ? 'neutral'
        : ($monthChangePercent >= 0 ? 'success' : 'danger');
@endphp

<x-ui.page-header
    eyebrow="Quản trị"
    title="Doanh thu"
    subtitle="Theo dõi dòng tiền nạp ví, giao dịch đã thanh toán và các lệnh đang chờ xử lý."
>
    <x-ui.button :href="route('deposits.index')" variant="outline">Xem trang nạp tiền</x-ui.button>
</x-ui.page-header>

<div class="admin-revenue-page space-y-4">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            class="revenue-stat-card revenue-stat-card--success"
            label="Tổng doanh thu"
            :value="$money($totalRevenue)"
            variant="success"
            :subtitle="number_format($paidOrdersCount) . ' giao dịch đã thanh toán'"
        />
        <x-ui.stat-card
            class="revenue-stat-card revenue-stat-card--primary"
            label="Doanh thu tháng này"
            :value="$money($monthRevenue)"
            :variant="$monthTrendVariant"
            :subtitle="$monthTrend"
        />
        <x-ui.stat-card
            class="revenue-stat-card revenue-stat-card--today"
            label="Doanh thu hôm nay"
            :value="$money($todayRevenue)"
            subtitle="Tổng giao dịch đã ghi nhận trong ngày"
        />
        <x-ui.stat-card
            class="revenue-stat-card revenue-stat-card--warning"
            label="Đang chờ nạp"
            :value="$money($pendingAmount)"
            variant="warning"
            :subtitle="number_format($pendingOrdersCount) . ' lệnh chưa thanh toán'"
        />
    </div>

    <x-ui.card class="admin-revenue-card revenue-summary-card overflow-hidden" padding="none">
        <div class="revenue-summary-grid">
            <div class="revenue-summary-item">
                <span>Khách đã nạp</span>
                <strong>{{ number_format($activeDepositors) }}</strong>
            </div>
            <div class="revenue-summary-item">
                <span>Giá trị trung bình</span>
                <strong>{{ $money($averagePaidOrder) }}</strong>
            </div>
            <div class="revenue-summary-item">
                <span>Tháng trước</span>
                <strong>{{ $money($lastMonthRevenue) }}</strong>
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-4 xl:grid-cols-7">
        <x-ui.card class="admin-revenue-card overflow-hidden xl:col-span-4" padding="none">
            <x-slot name="header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="mb-0.5 text-base font-bold text-slate-950">Doanh thu 6 tháng gần nhất</h2>
                        <p class="mb-0 text-xs text-slate-500">Chỉ tính các giao dịch đã thanh toán.</p>
                    </div>
                    <x-ui.badge variant="primary">6 tháng</x-ui.badge>
                </div>
            </x-slot>

            <div class="revenue-chart-body">
                <div class="revenue-chart" style="--chart-count: {{ max(1, count($monthlyRevenue)) }};">
                    @foreach($monthlyRevenue as $month)
                        @php
                            $height = $maxMonthlyRevenue > 0 ? max(10, round(($month['total'] / $maxMonthlyRevenue) * 100)) : 10;
                        @endphp
                        <div class="revenue-chart-column" title="{{ $month['label'] }}: {{ $money($month['total']) }}">
                            <div class="revenue-chart-value">{{ $money($month['total']) }}</div>
                            <div class="revenue-bar-track">
                                <div class="revenue-bar" style="height: {{ $height }}%;"></div>
                            </div>
                            <div class="revenue-chart-label">
                                <strong>{{ $month['label'] }}</strong>
                                <span>{{ $month['count'] }} GD</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="admin-revenue-card overflow-hidden xl:col-span-3" padding="none">
            <x-slot name="header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="mb-0.5 text-base font-bold text-slate-950">Top khách nạp tiền</h2>
                        <p class="mb-0 text-xs text-slate-500">Xếp hạng theo tổng số tiền đã thanh toán.</p>
                    </div>
                    <x-ui.badge variant="success">Top {{ $topDepositors->count() }}</x-ui.badge>
                </div>
            </x-slot>

            <div class="revenue-rank-list">
                @forelse($topDepositors as $depositor)
                    <div class="revenue-rank-row">
                        <div class="revenue-rank-number">{{ $loop->iteration }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-slate-950">{{ $depositor->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $depositor->email }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-sm font-bold text-emerald-600">{{ $money($depositor->total_amount) }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">{{ number_format($depositor->order_count) }} GD</div>
                        </div>
                    </div>
                @empty
                    <div class="revenue-empty-state">Chưa có khách hàng thanh toán.</div>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    <x-ui.card class="admin-revenue-card overflow-hidden" padding="none">
        <x-slot name="header">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="mb-0.5 text-base font-bold text-slate-950">Giao dịch đã thanh toán gần nhất</h2>
                    <p class="mb-0 text-xs text-slate-500">{{ $recentOrders->count() }} giao dịch mới nhất.</p>
                </div>
                <x-ui.badge variant="success">Paid</x-ui.badge>
            </div>
        </x-slot>

        <div class="grid gap-3 p-4 lg:hidden">
            @forelse($recentOrders as $order)
                @php
                    $paidAt = $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : '-';
                @endphp
                <article class="revenue-mobile-transaction">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-mono text-sm font-bold text-slate-950">{{ $order->code }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $paidAt }}</div>
                        </div>
                        <x-ui.badge variant="success">Paid</x-ui.badge>
                    </div>
                    <div class="mb-3 font-mono text-lg font-bold text-emerald-600">{{ $money($order->amount) }}</div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="block font-bold uppercase tracking-wide text-slate-400">Khách hàng</span>
                            <span class="mt-1 block truncate font-semibold text-slate-700">{{ $order->user->name ?? 'Unknown' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block font-bold uppercase tracking-wide text-slate-400">Nguồn</span>
                            <span class="mt-1 block truncate font-semibold text-slate-700">{{ $order->provider ?? '-' }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="revenue-empty-state">Chưa có giao dịch đã thanh toán.</div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="revenue-table min-w-full border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Mã nạp</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Khách hàng</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Số tiền</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nguồn</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
                            <td class="px-5 py-4 font-mono text-sm font-bold text-slate-950">{{ $order->code }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ $order->user->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-500">{{ $order->user->email ?? '' }}</div>
                            </td>
                            <td class="px-5 py-4 font-mono text-sm font-bold text-emerald-600">{{ $money($order->amount) }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $order->provider ?? '-' }}</td>
                            <td class="px-5 py-4 text-right text-sm text-slate-500">
                                {{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Chưa có giao dịch đã thanh toán.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection

@push('styles')
<style>
    .admin-revenue-page {
        padding-bottom: 4px;
    }

    .revenue-stat-card {
        min-height: 112px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px;
        position: relative;
        overflow: hidden;
    }

    .revenue-stat-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 3px;
        background: #3b82f6;
    }

    .revenue-stat-card--success::before { background: #10b981; }
    .revenue-stat-card--warning::before { background: #f59e0b; }
    .revenue-stat-card--today::before { background: #06b6d4; }

    .revenue-stat-card > .flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .revenue-stat-card > .flex > div:first-child {
        font-size: 11px;
        line-height: 1rem;
        letter-spacing: .06em;
    }

    .revenue-stat-card > .flex > div:last-child {
        font-size: 23px;
        line-height: 1.1;
        white-space: normal;
        word-break: break-word;
    }

    .revenue-stat-card > div:nth-child(2) {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        border-width: 1px;
        border-radius: 9999px;
        padding: 5px 9px;
        font-size: 11px;
        line-height: 1rem;
    }

    .admin-revenue-card > .border-b {
        padding: 14px 18px;
    }

    .revenue-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .revenue-summary-item {
        padding: 14px 18px;
        border-right: 1px solid #e2e8f0;
    }

    .revenue-summary-item:last-child {
        border-right: 0;
    }

    .revenue-summary-item span {
        display: block;
        margin-bottom: 5px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
    }

    .revenue-summary-item strong {
        display: block;
        font-family: 'Roboto Mono', monospace;
        font-size: 18px;
        line-height: 1.2;
        color: #0f172a;
    }

    .revenue-chart-body {
        padding: 18px;
    }

    .revenue-chart {
        min-height: 238px;
        display: grid;
        grid-template-columns: repeat(var(--chart-count), minmax(54px, 1fr));
        align-items: end;
        gap: 12px;
    }

    .revenue-chart-column {
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 9px;
    }

    .revenue-chart-value {
        min-height: 28px;
        max-width: 100%;
        color: #334155;
        font-family: 'Roboto Mono', monospace;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.25;
        text-align: center;
        word-break: break-word;
    }

    .revenue-bar-track {
        width: 100%;
        max-width: 46px;
        height: 136px;
        display: flex;
        align-items: end;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
    }

    .revenue-bar {
        width: 100%;
        border-radius: 8px 8px 0 0;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 100%);
        transition: height .25s ease, filter .2s ease;
    }

    .revenue-chart-column:hover .revenue-bar {
        filter: brightness(1.08);
    }

    .revenue-chart-label {
        text-align: center;
    }

    .revenue-chart-label strong,
    .revenue-chart-label span {
        display: block;
    }

    .revenue-chart-label strong {
        color: #334155;
        font-size: 11px;
        line-height: 1rem;
    }

    .revenue-chart-label span {
        color: #64748b;
        font-size: 10px;
        line-height: 1rem;
    }

    .revenue-rank-list {
        padding: 8px 0;
    }

    .revenue-rank-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
    }

    .revenue-rank-row:last-child {
        border-bottom: 0;
    }

    .revenue-rank-row:hover {
        background: #f8fafc;
    }

    .revenue-rank-number {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        font-family: 'Roboto Mono', monospace;
        font-size: 12px;
        font-weight: 800;
    }

    .revenue-table th {
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .revenue-table td {
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
    }

    .revenue-mobile-transaction {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 14px;
    }

    .revenue-empty-state {
        padding: 28px 18px;
        text-align: center;
        color: #64748b;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .revenue-stat-card {
            min-height: 106px;
            padding: 14px;
        }

        .revenue-stat-card > .flex > div:last-child {
            font-size: 19px;
        }

        .revenue-summary-grid {
            grid-template-columns: 1fr;
        }

        .revenue-summary-item {
            border-right: 0;
            border-bottom: 1px solid #e2e8f0;
            padding: 13px 16px;
        }

        .revenue-summary-item:last-child {
            border-bottom: 0;
        }

        .revenue-chart-body {
            padding: 14px 12px 16px;
            overflow-x: auto;
        }

        .revenue-chart {
            min-width: 520px;
            min-height: 220px;
            gap: 10px;
        }

        .revenue-bar-track {
            height: 122px;
        }
    }
</style>
@endpush
