@props([
    'variant' => null,
    'status' => null,
    'dot' => true,
])

@php
    $normalized = mb_strtolower((string) ($status ?: $variant));

    $containsAny = function ($value, array $needles) {
        foreach ($needles as $needle) {
            if (strpos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    };

    if ($containsAny($normalized, ['running', 'dang chay', 'đang chạy', 'paid', 'hoat dong', 'hoạt động'])) {
        $statusVariant = 'success';
    } elseif ($containsAny($normalized, ['error', 'loi', 'terminated', 'stopped', 'tat'])) {
        $statusVariant = 'danger';
    } elseif ($containsAny($normalized, ['pending', 'khoi tao', 'cho'])) {
        $statusVariant = 'warning';
    } else {
        $statusVariant = $variant ?: 'neutral';
    }

    $variants = [
        'success' => 'border-success-100 bg-success-50 text-success-700',
        'warning' => 'border-warning-100 bg-warning-50 text-warning-700',
        'danger' => 'border-danger-100 bg-danger-50 text-danger-700',
        'primary' => 'border-brand-100 bg-brand-50 text-brand-700',
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-700',
    ];

    $classes = 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold leading-none ' . ($variants[$statusVariant] ?? $variants['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot->isEmpty() ? ($status ?: ucfirst($statusVariant)) : $slot }}
</span>
