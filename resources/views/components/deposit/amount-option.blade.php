@props([
    'amount',
])

<button
    type="button"
    class="deposit-option inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-brand-200 bg-white px-3 text-sm font-semibold text-brand-700 transition hover:border-brand-600 hover:bg-brand-50"
    data-amount="{{ (int) $amount }}"
>
    {{ number_format((int) $amount, 0, ',', '.') }}
</button>
