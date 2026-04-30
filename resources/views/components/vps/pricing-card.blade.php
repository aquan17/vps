@props([
    'id',
    'plan',
    'checked' => false,
    'featured' => false,
])

@php
    $cores = $plan['api_cores'] ?? $plan['cores'] ?? '-';
    $ram = $plan['api_ram'] ?? $plan['ram'] ?? '-';
    $disk = $plan['disk'] ?? '-';
    $price = (int) ($plan['price_per_month'] ?? $plan['price_per_day'] ?? 0);
@endphp

<div class="relative h-full">
    <button
        type="button"
        x-bind:disabled="submitting"
        x-on:click="selectPlan('{{ $id }}')"
        x-bind:class="plan === '{{ $id }}' ? 'border-brand-600 bg-brand-50 ring-2 ring-brand-100' : 'border-slate-200 bg-white'"
        class="block h-full w-full cursor-pointer rounded-lg border p-3 text-left shadow-sm transition-colors hover:border-brand-300 sm:p-4"
    >
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate text-base font-bold text-slate-950">{{ $plan['name'] }}</h3>
                <p class="mb-0 mt-0.5 truncate text-xs font-medium text-slate-500">{{ $plan['desc'] ?? 'VPS hiệu năng cao' }}</p>
            </div>

            @if($featured)
                <span class="shrink-0 rounded-full bg-brand-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm">
                    Khuyên dùng
                </span>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2">
                <span class="block text-[11px] font-semibold uppercase text-slate-500">CPU</span>
                <strong class="mt-0.5 block font-mono text-sm text-slate-950">{{ $cores }} vCPU</strong>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2">
                <span class="block text-[11px] font-semibold uppercase text-slate-500">RAM</span>
                <strong class="mt-0.5 block font-mono text-sm text-slate-950">{{ $ram }} GB</strong>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2">
                <span class="block text-[11px] font-semibold uppercase text-slate-500">Disk</span>
                <strong class="mt-0.5 block font-mono text-sm text-slate-950">{{ $disk }} GB NVMe</strong>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2">
                <span class="block text-[11px] font-semibold uppercase text-slate-500">Network</span>
                <strong class="mt-0.5 block font-mono text-sm text-slate-950">1 Gbps</strong>
            </div>
        </div>

        <div class="mt-3 flex items-end justify-between gap-3 border-t border-slate-200 pt-3">
            <span class="text-xs font-semibold text-slate-500">Giá thuê</span>
            <div class="text-right">
                <span class="font-mono text-lg font-bold text-slate-950">{{ number_format($price, 0, ',', '.') }}</span>
                <span class="block text-xs font-semibold text-slate-500">VND / tháng</span>
            </div>
        </div>
    </button>
</div>
