@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'md',
    'variant' => 'default',
])

@php
    $variants = [
        'default' => 'border-slate-200 bg-white shadow-soft',
        'flat' => 'border-slate-200 bg-white',
        'muted' => 'border-slate-200 bg-slate-50',
        'danger' => 'border-danger-100 bg-danger-50',
    ];

    $paddingClasses = [
        'none' => '',
        'sm' => 'p-3',
        'md' => 'p-4',
        'lg' => 'p-5',
    ];

    $classes = 'rounded-card border ' . ($variants[$variant] ?? $variants['default']);
    $bodyClass = $paddingClasses[$padding] ?? $paddingClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($title || $subtitle || isset($header))
        <div class="border-b border-slate-200 px-4 py-2.5">
            @isset($header)
                {{ $header }}
            @else
                @if($title)
                    <h2 class="mb-0 text-sm font-bold text-slate-950">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="mb-0 mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            @endisset
        </div>
    @endif

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
