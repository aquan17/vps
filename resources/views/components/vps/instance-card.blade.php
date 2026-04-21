@props([
    'instance',
])

@php
    $expiresAt = $instance->expires_at
        ? $instance->expires_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
        : 'Chưa có hạn';
@endphp

<article {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white p-5 shadow-soft']) }}>
    <div class="mb-4 flex items-start justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg text-brand-700">
                {{ $instance->os === 'windows' ? 'Win' : 'Linux' }}
            </div>
            <div class="min-w-0">
                <h2 class="mb-1 truncate text-base font-bold text-slate-950">{{ $instance->name }}</h2>
                <p class="mb-0 text-sm text-slate-500">{{ $instance->zone }}</p>
            </div>
        </div>

        <x-ui.badge :status="$instance->status">{{ $instance->status }}</x-ui.badge>
    </div>

    <div class="mb-4 grid grid-cols-3 gap-2">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
            <div class="text-xs font-bold uppercase text-slate-500">CPU</div>
            <div class="mt-1 font-mono text-sm font-bold text-slate-950">{{ $instance->cpu }}C</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
            <div class="text-xs font-bold uppercase text-slate-500">RAM</div>
            <div class="mt-1 font-mono text-sm font-bold text-slate-950">{{ $instance->ram }}GB</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
            <div class="text-xs font-bold uppercase text-slate-500">IP</div>
            <div class="mt-1 truncate font-mono text-sm font-bold text-brand-700">{{ $instance->public_ip ?? 'Pending' }}</div>
        </div>
    </div>

    <div class="mb-4 text-xs font-semibold text-danger-700">
        Hết hạn: {{ $expiresAt }}
    </div>

    <div class="flex gap-2">
        <x-ui.button :href="route('vps.show', $instance->id)" variant="primary" full-width>
            Quản lý
        </x-ui.button>

        <form action="{{ route('vps.destroy', $instance->id) }}" method="POST" class="m-0 flex-1" onsubmit="return confirm('Xóa VPS ' + @json($instance->name) + '?');">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger" full-width>
                Xóa
            </x-ui.button>
        </form>
    </div>
</article>
