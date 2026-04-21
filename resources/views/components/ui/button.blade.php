@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'fullWidth' => false,
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg border font-semibold no-underline transition ui-focus disabled:pointer-events-none disabled:opacity-60';

    $variants = [
        'primary' => 'border-brand-600 bg-brand-600 text-white shadow-sm hover:border-brand-700 hover:bg-brand-700',
        'secondary' => 'border-slate-300 bg-white text-slate-700 shadow-sm hover:border-slate-400 hover:bg-slate-50',
        'ghost' => 'border-transparent bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-950',
        'danger' => 'border-danger-600 bg-danger-600 text-white shadow-sm hover:border-danger-700 hover:bg-danger-700',
        'success' => 'border-success-600 bg-success-600 text-white shadow-sm hover:border-success-700 hover:bg-success-700',
        'warning' => 'border-warning-600 bg-warning-600 text-white shadow-sm hover:border-warning-700 hover:bg-warning-700',
    ];

    $sizes = [
        'sm' => 'min-h-9 px-3 text-xs',
        'md' => 'min-h-10 px-4 text-sm',
        'lg' => 'min-h-12 px-5 text-base',
    ];

    $classes = trim($base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($fullWidth ? 'w-full' : ''));
@endphp

@if($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
