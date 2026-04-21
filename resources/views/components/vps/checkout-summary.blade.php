@props([
    'balance' => 0,
    'depositRoute',
    'sticky' => true,
])

@php
    $balance = (int) $balance;
    $classes = 'rounded-card border border-slate-200 bg-white p-5 shadow-soft';
    if ($sticky) {
        $classes .= ' lg:sticky lg:top-6';
    }
@endphp

<aside {{ $attributes->merge(['class' => $classes]) }}>
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h2 class="mb-1 text-lg font-bold text-slate-950">Tóm tắt đơn hàng</h2>
            <p class="mb-0 text-sm text-slate-500">Kiểm tra cấu hình trước khi thanh toán.</p>
        </div>
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">VPS</span>
    </div>

    <div class="space-y-3 border-y border-slate-200 py-4">
        <div class="flex justify-between gap-4 text-sm">
            <span class="text-slate-500">Gói</span>
            <strong class="text-right text-slate-950" x-text="selectedPlanName || 'Chưa chọn'"></strong>
        </div>
        <div class="flex justify-between gap-4 text-sm">
            <span class="text-slate-500">Thời hạn</span>
            <strong class="text-right text-slate-950" x-text="durationLabel"></strong>
        </div>
        <div class="flex justify-between gap-4 text-sm">
            <span class="text-slate-500">Cấu hình</span>
            <strong class="text-right font-mono text-slate-950" x-text="selectedSpecs"></strong>
        </div>
        <div class="flex justify-between gap-4 text-sm">
            <span class="text-slate-500">Số dư</span>
            <strong class="text-right font-mono text-success-700">{{ number_format($balance, 0, ',', '.') }} VND</strong>
        </div>
    </div>

    <div class="mt-4">
        <div class="flex items-end justify-between gap-4">
            <span class="text-sm font-semibold text-slate-500">Tổng chi phí</span>
            <strong class="text-right font-mono text-2xl font-bold text-brand-700" x-text="formatMoney(totalPrice)"></strong>
        </div>

        <div class="mt-3 rounded-lg border border-danger-100 bg-danger-50 p-3 text-sm text-danger-700" x-show="missingAmount > 0" x-cloak>
            Cần nạp thêm <strong x-text="formatMoney(missingAmount)"></strong>.
        </div>
    </div>

    <div class="mt-5">
        <x-ui.button
            type="submit"
            variant="primary"
            size="lg"
            full-width
            x-show="missingAmount <= 0"
            x-bind:disabled="!plan || submitting"
        >
            <span x-text="submitting ? 'Đang khởi tạo...' : 'Thanh toán & khởi tạo VPS'"></span>
        </x-ui.button>

        <a
            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg border border-success-600 bg-success-600 px-5 text-base font-semibold text-white no-underline shadow-sm transition ui-focus hover:border-success-700 hover:bg-success-700"
            x-show="missingAmount > 0"
            x-bind:href="'{{ $depositRoute }}?amount=' + missingAmount"
            x-cloak
        >
            Nạp thêm <span x-text="formatMoney(missingAmount)"></span>
        </a>
    </div>
</aside>
