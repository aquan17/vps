@extends('layouts.app')

@section('title', 'Quản lý voucher - CloudVPS')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $money = fn ($value) => number_format((int) $value, 0, ',', '.') . ' VND';
    $formatDate = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
@endphp

<x-ui.page-header
    eyebrow="Quản trị"
    title="Voucher"
    subtitle="Tạo mã giảm giá cho luồng thanh toán VPS và theo dõi lượt sử dụng."
>
    <x-ui.button :href="route('admin.revenue')" variant="outline">Xem doanh thu</x-ui.button>
</x-ui.page-header>

<div class="admin-voucher-page space-y-4">
    <div class="admin-voucher-stats">
        <div class="admin-voucher-stat">
            <span>Tổng mã</span>
            <strong>{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="admin-voucher-stat">
            <span>Đang bật</span>
            <strong>{{ number_format($stats['active']) }}</strong>
        </div>
        <div class="admin-voucher-stat">
            <span>Lượt dùng</span>
            <strong>{{ number_format($stats['used']) }}</strong>
        </div>
        <div class="admin-voucher-stat">
            <span>Đã giảm</span>
            <strong>{{ $money($stats['discount']) }}</strong>
        </div>
    </div>

    <x-ui.card padding="md">
        <form method="POST" action="{{ route('admin.vouchers.store') }}" class="admin-voucher-form">
            @csrf
            <div class="admin-voucher-form-head">
                <div>
                    <h2 class="mb-1 text-lg font-bold text-slate-950">Tạo voucher mới</h2>
                    <p class="mb-0 text-sm text-slate-500">Để trống gói hoặc thời hạn nếu voucher áp dụng cho tất cả.</p>
                </div>
                <button type="submit" class="btn btn-primary fw-bold">Tạo voucher</button>
            </div>

            @include('admin.partials.voucher-fields', ['voucher' => null, 'plans' => $plans, 'durations' => $durations])
        </form>
    </x-ui.card>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-4">
            @forelse($vouchers as $voucher)
                @php
                    $formId = 'voucher-form-' . $voucher->id;
                @endphp
                <x-ui.card padding="md" class="admin-voucher-item">
                    <div class="admin-voucher-item-head">
                        <div class="min-w-0">
                            <div class="admin-voucher-code">
                                {{ $voucher->code }}
                                <span class="admin-voucher-status {{ $voucher->is_active ? 'is-active' : 'is-off' }}">
                                    {{ $voucher->is_active ? 'Đang bật' : 'Đã tắt' }}
                                </span>
                            </div>
                            <div class="admin-voucher-meta">
                                <span>{{ $voucher->type === 'percent' ? $voucher->value . '%' : $money($voucher->value) }}</span>
                                <span>{{ number_format($voucher->redemptions_count) }} lượt dùng</span>
                                <span>Đã giảm {{ $money($voucher->discount_total ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="admin-voucher-actions">
                            <button type="submit" form="{{ $formId }}" class="btn btn-primary btn-sm fw-bold">Lưu</button>
                            <form method="POST" action="{{ route('admin.vouchers.toggle', $voucher) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary btn-sm fw-bold">{{ $voucher->is_active ? 'Tắt' : 'Bật' }}</button>
                            </form>
                            @if($voucher->redemptions_count === 0)
                                <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Xóa voucher {{ $voucher->code }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm fw-bold">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.vouchers.update', $voucher) }}" class="admin-voucher-form mt-4">
                        @csrf
                        @method('PUT')
                        @include('admin.partials.voucher-fields', ['voucher' => $voucher, 'plans' => $plans, 'durations' => $durations])
                    </form>
                </x-ui.card>
            @empty
                <x-ui.card padding="lg">
                    <div class="text-center text-sm font-semibold text-slate-500">Chưa có voucher nào.</div>
                </x-ui.card>
            @endforelse

            @if($vouchers->hasPages())
                <x-ui.card padding="sm">
                    {{ $vouchers->links() }}
                </x-ui.card>
            @endif
        </div>

        <x-ui.card padding="none" class="overflow-hidden">
            <x-slot name="header">
                <div>
                    <h2 class="mt-2 mb-0.5 text-base font-bold text-slate-950">Lượt dùng gần đây</h2>
                    <p class="mb-0 text-xs text-slate-500">{{ $recentRedemptions->count() }} giao dịch mới nhất.</p>
                </div>
            </x-slot>

            <div class="admin-voucher-redemptions">
                @forelse($recentRedemptions as $redemption)
                    <div class="admin-voucher-redemption">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-mono text-sm font-bold text-slate-950">{{ $redemption->code }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $redemption->user->email ?? 'Không rõ' }}</div>
                            </div>
                            <strong class="font-mono text-sm text-success-700">-{{ $money($redemption->discount_amount) }}</strong>
                        </div>
                        <div class="mt-2 flex justify-between gap-3 text-xs text-slate-500">
                            <span class="shrink-0">{{ $redemption->created_at->format('d/m/Y H:i') }}</span>
                            <span class="truncate text-right">{{ $redemption->vpsInstance->name ?? 'VPS đã xóa' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-center text-sm text-slate-500">Chưa có lượt dùng voucher.</div>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
@endsection

@push('styles')
<style>
    .admin-voucher-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .admin-voucher-stat {
        min-height: 76px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        padding: 14px 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
    }

    .admin-voucher-stat span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .admin-voucher-stat strong {
        display: block;
        margin-top: 6px;
        color: #0f172a;
        font-family: 'Roboto Mono', monospace;
        font-size: 20px;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .admin-voucher-form-head,
    .admin-voucher-item-head,
    .admin-voucher-actions {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .admin-voucher-actions {
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .admin-voucher-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .voucher-fields {
        display: grid;
        gap: 14px;
    }

    .voucher-field-section {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        padding: 14px;
    }

    .voucher-field-section--main {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .voucher-field-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .voucher-field-section-head h3 {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.25;
    }

    .voucher-field-section-head p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.45;
    }

    .voucher-field-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .voucher-field-grid--main {
        grid-template-columns: minmax(220px, 1.2fr) minmax(160px, .8fr) minmax(140px, .65fr);
    }

    .voucher-control label,
    .voucher-group-label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
    }

    .voucher-control .form-control,
    .voucher-control .form-select {
        min-height: 42px;
        border-color: #cbd5e1;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
    }

    .voucher-control--code .form-control {
        font-family: 'Roboto Mono', monospace;
        letter-spacing: .04em;
    }

    .voucher-switch {
        display: inline-flex;
        flex-shrink: 0;
        align-items: center;
        gap: 8px;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        background: #f0fdf4;
        padding: 7px 10px;
        color: #15803d;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .voucher-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .voucher-switch span {
        width: 30px;
        height: 18px;
        border-radius: 999px;
        background: #86efac;
        position: relative;
        transition: background .18s ease;
    }

    .voucher-switch span::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 15px;
        width: 12px;
        height: 12px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .22);
        transition: transform .18s ease;
    }

    .voucher-switch input:not(:checked) + span {
        background: #cbd5e1;
    }

    .voucher-switch input:not(:checked) + span::after {
        transform: translateX(-12px);
    }

    .voucher-scope-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 14px;
    }

    .voucher-tile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .voucher-tile-grid--compact {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .voucher-tile {
        position: relative;
        display: grid;
        grid-template-columns: 20px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        min-height: 48px;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        padding: 8px 10px;
        cursor: pointer;
        transition: border-color .18s ease, background .18s ease;
    }

    .voucher-tile input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .voucher-tile i {
        display: inline-flex;
        flex: 0 0 18px;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        border: 2px solid #94a3b8;
        border-radius: 6px;
        background: #ffffff;
        color: #ffffff;
        font-style: normal;
        transition: border-color .18s ease, background .18s ease;
    }

    .voucher-tile i::after {
        content: '';
        width: 8px;
        height: 4px;
        border-left: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(-45deg) translate(1px, -1px);
        opacity: 0;
        transition: opacity .18s ease;
    }

    .voucher-tile input:checked + i {
        border-color: #2563eb;
        background: #2563eb;
    }

    .voucher-tile input:checked + i::after {
        opacity: 1;
    }

    .voucher-tile:focus-within {
        border-color: #2563eb;
    }

    .voucher-tile strong,
    .voucher-tile small {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .voucher-tile strong {
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
    }

    .voucher-tile small {
        margin-top: 1px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .admin-voucher-wide {
        grid-column: span 2;
    }

    .admin-voucher-checks {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .admin-voucher-check {
        display: flex;
        min-height: 38px;
        align-items: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        padding: 8px 10px;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .admin-voucher-code {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        color: #0f172a;
        font-family: 'Roboto Mono', monospace;
        font-size: 18px;
        font-weight: 900;
    }

    .admin-voucher-status {
        border-radius: 999px;
        padding: 4px 9px;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 900;
    }

    .admin-voucher-status.is-active {
        background: #dcfce7;
        color: #15803d;
    }

    .admin-voucher-status.is-off {
        background: #fee2e2;
        color: #b91c1c;
    }

    .admin-voucher-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .admin-voucher-meta span {
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        padding: 4px 8px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
    }

    .admin-voucher-redemption {
        margin: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
    }

    .admin-voucher-redemption:last-child {
        margin-bottom: 12px;
    }

    @media (max-width: 1180px) {
        .admin-voucher-stats,
        .admin-voucher-grid,
        .voucher-field-grid,
        .voucher-field-grid--main,
        .voucher-scope-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .admin-voucher-stats,
        .admin-voucher-grid,
        .admin-voucher-checks,
        .voucher-field-grid,
        .voucher-field-grid--main,
        .voucher-scope-grid,
        .voucher-tile-grid,
        .voucher-tile-grid--compact {
            grid-template-columns: 1fr;
        }

        .voucher-field-section-head {
            flex-direction: column;
        }

        .admin-voucher-wide {
            grid-column: span 1;
        }

        .admin-voucher-form-head,
        .admin-voucher-item-head {
            flex-direction: column;
        }

        .admin-voucher-actions {
            justify-content: flex-start;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.querySelectorAll('.admin-voucher-money').forEach(function(input) {
        input.addEventListener('input', function() {
            const digits = this.value.replace(/\D/g, '');
            this.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });
</script>
@endpush
