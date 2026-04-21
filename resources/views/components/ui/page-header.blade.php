@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between']) }}>
    <div class="min-w-0">
        @if($eyebrow)
            <div class="mb-1.5 text-xs font-bold uppercase tracking-wider text-brand-600">{{ $eyebrow }}</div>
        @endif
        <h1 class="mb-0 text-xl font-bold tracking-tight text-slate-950 md:text-2xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mb-0 mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if(!$slot->isEmpty())
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
