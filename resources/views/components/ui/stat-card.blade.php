@props([
    'label',
    'value',
    'subtitle' => null,
    'variant' => 'primary',
])

@php
    $variants = [
        'primary' => 'border-brand-100 bg-brand-50 text-brand-700',
        'success' => 'border-success-100 bg-success-50 text-success-700',
        'warning' => 'border-warning-100 bg-warning-50 text-warning-700',
        'danger' => 'border-danger-100 bg-danger-50 text-danger-700',
        'neutral' => 'border-slate-200 bg-slate-50 text-slate-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white p-3 shadow-soft']) }}>
    <div class="flex items-center justify-between gap-4">
        <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{{ $label }}</div>
        <div class="font-mono text-lg font-bold text-slate-950">{{ $value }}</div>
    </div>
    @if($subtitle)
        <div class="mt-1 text-[10px] font-bold {{ $variants[$variant] ?? $variants['neutral'] }}">
            {{ $subtitle }}
        </div>
    @endif
</div>
