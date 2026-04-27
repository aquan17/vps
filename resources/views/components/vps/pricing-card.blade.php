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
        class="block h-full w-full cursor-pointer rounded-card border p-5 text-left shadow-sm transition-colors hover:border-brand-300"
    >
        @if($featured)
        <div class="absolute right-3 top-3 rounded-full bg-brand-600 px-2.5 py-1 text-xs font-bold text-white shadow-sm sm:right-4 sm:top-4 sm:px-3">
                Khuyên dùng
            </div>
        @endif

        <div class="pr-20 sm:pr-24">
            <h3 class="mb-1 text-lg font-bold text-slate-950">{{ $plan['name'] }}</h3>
            <p class="mb-5 text-sm leading-5 text-slate-500">{{ $plan['desc'] ?? 'VPS hiệu năng cao' }}</p>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-4 text-sm">
                <span class="text-slate-500">CPU</span>
                <strong class="font-mono text-slate-950">{{ $cores }} vCPU</strong>
            </div>
            <div class="flex items-center justify-between gap-4 text-sm">
                <span class="text-slate-500">RAM</span>
                <strong class="font-mono text-slate-950">{{ $ram }} GB</strong>
            </div>
            <div class="flex items-center justify-between gap-4 text-sm">
                <span class="text-slate-500">Disk</span>
                <strong class="font-mono text-slate-950">{{ $disk }} GB NVMe</strong>
            </div>
            <div class="flex items-center justify-between gap-4 text-sm">
                <span class="text-slate-500">Network</span>
                <strong class="font-mono text-slate-950">1 Gbps</strong>
            </div>
        </div>

        <div class="mt-5 border-t border-slate-200 pt-4">
            <span class="font-mono text-xl font-bold text-slate-950 sm:text-2xl">{{ number_format($price, 0, ',', '.') }}</span>
            <span class="text-sm font-medium text-slate-500"> VND / tháng</span>
        </div>
    </button>
</div>
