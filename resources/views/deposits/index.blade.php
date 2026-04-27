@extends('layouts.app')

@section('title', 'Nạp tiền VietQR - CloudVPS')
@section('meta_description', 'Tạo lệnh nạp tiền VietQR cho tài khoản CloudVPS và theo dõi lịch sử giao dịch tự động.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $initialAmount = (int) preg_replace('/\D/', '', (string) old('amount', request('amount', 100000)));
    $initialAmount = min(50000000, max(1000, $initialAmount));
@endphp

<x-ui.page-header
    title="Nạp tiền"
    subtitle="Tạo mã VietQR riêng cho từng giao dịch để hệ thống tự đối soát."
/>

<div class="grid gap-4 sm:gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
    <x-ui.card title="Tạo lệnh nạp" subtitle="Chuyển khoản đúng số tiền và nội dung để được cộng tự động." padding="lg" class="deposit-card">
        <form action="{{ route('deposits.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="depositAmount" class="mb-2 block text-sm font-semibold text-slate-700">Số tiền</label>
                <div class="flex overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                    <input
                        type="text"
                        id="depositAmount"
                        name="amount"
                        class="min-h-12 flex-1 border-0 px-4 text-base font-semibold text-slate-950 focus:ring-0"
                        inputmode="numeric"
                        autocomplete="off"
                        value="{{ number_format($initialAmount, 0, ',', '.') }}"
                        required
                    >
                    <span class="flex min-h-12 items-center border-l border-slate-200 bg-slate-50 px-4 text-sm font-bold text-slate-500">VND</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-2">
                @foreach([50000, 100000, 200000, 500000, 1000000, 2000000, 5000000, 10000000] as $amount)
                    <button
                        type="button"
                        class="deposit-option min-h-10 rounded-lg border border-brand-200 bg-white px-3 text-sm font-semibold text-brand-700 transition-colors hover:border-brand-600 hover:bg-brand-50"
                        data-amount="{{ $amount }}"
                    >
                        {{ number_format($amount, 0, ',', '.') }}
                    </button>
                @endforeach
            </div>

            <x-ui.button type="submit" size="lg" full-width>
                Tạo mã QR
            </x-ui.button>
        </form>

        <div class="mt-5 rounded-card border border-brand-100 bg-brand-50 p-4 text-sm text-brand-700">
            <div>Tài khoản nhận: <strong>{{ config('deposit.account_no') }}</strong></div>
            <div class="mt-1">Ngân hàng: <strong>{{ config('deposit.bank_id') }}</strong></div>
            <div class="mt-1">Chủ tài khoản: <strong>{{ config('deposit.account_name') }}</strong></div>
        </div>
    </x-ui.card>

    <x-ui.card padding="none" class="deposit-card overflow-hidden">
        <x-slot name="header">
            <div>
                <h2 class="mb-1 text-lg font-bold text-slate-950">Lịch sử nạp</h2>
                <p class="mb-0 text-sm text-slate-500">{{ $orders->count() }} giao dịch gần nhất</p>
            </div>
        </x-slot>

        <div class="grid gap-3 p-4 lg:hidden">
            @forelse($orders as $order)
                <article class="rounded-card border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="break-all font-mono text-sm font-bold text-slate-950">{{ $order->code }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <x-ui.badge :variant="$order->status === 'paid' ? 'success' : 'warning'">
                            {{ $order->status === 'paid' ? 'Đã thanh toán' : 'Chờ chuyển khoản' }}
                        </x-ui.badge>
                    </div>
                    <div class="mb-3 font-mono text-lg font-bold text-brand-700">{{ number_format($order->amount, 0, ',', '.') }} VND</div>
                    <x-ui.button :href="route('deposits.show', $order->id)" variant="secondary" full-width>Xem QR</x-ui.button>
                </article>
            @empty
                <div class="py-10 text-center text-sm text-slate-500">Chưa có lệnh nạp nào.</div>
            @endforelse
        </div>

        <div class="hidden lg:block">
            <table class="deposit-history-table w-full table-fixed border-collapse">
                <colgroup>
                    <col class="w-[34%]">
                    <col class="w-[22%]">
                    <col class="w-[24%]">
                    <col class="w-[20%]">
                </colgroup>
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Mã nạp</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Số tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="truncate font-mono text-sm font-bold text-slate-950">{{ $order->code }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-4 font-mono text-sm font-bold text-brand-700">{{ number_format($order->amount, 0, ',', '.') }} VND</td>
                            <td class="px-4 py-4">
                                <span class="deposit-status-pill {{ $order->status === 'paid' ? 'is-paid' : 'is-pending' }}">
                                    {{ $order->status === 'paid' ? 'Đã thanh toán' : 'Chờ chuyển khoản' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <x-ui.button :href="route('deposits.show', $order->id)" variant="secondary" size="sm">Xem QR</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Chưa có lệnh nạp nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection

@push('styles')
<style>
    .deposit-card > .border-b {
        padding: 16px 24px;
    }

    .deposit-card {
        min-width: 0;
    }

    .deposit-option {
        min-width: 0;
        white-space: nowrap;
    }

    .deposit-card strong {
        word-break: break-word;
    }

    .deposit-card article {
        min-width: 0;
    }

    .deposit-history-table th,
    .deposit-history-table td {
        min-width: 0;
        vertical-align: middle;
    }

    .deposit-status-pill {
        display: inline-flex;
        max-width: 100%;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
    }

    .deposit-status-pill.is-paid {
        background: #dcfce7;
        color: #15803d;
    }

    .deposit-status-pill.is-pending {
        background: #fef3c7;
        color: #b45309;
    }

    @media (max-width: 768px) {
        .deposit-card > .border-b {
            padding: 14px 18px;
        }

        .deposit-card {
            border-radius: 14px;
        }

        .deposit-option {
            font-size: 13px;
            padding-left: 10px;
            padding-right: 10px;
        }
    }

    @media (max-width: 480px) {
        .deposit-card > .border-b {
            padding: 12px 14px;
        }

        .deposit-card .p-4 {
            padding: 12px;
        }

        .deposit-card .mt-5.rounded-card {
            margin-top: 14px;
        }

        .deposit-option {
            min-height: 38px;
            font-size: 12px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const depositAmountInput = document.getElementById('depositAmount');

    function formatDepositAmount(value) {
        const digits = value.replace(/\D/g, '');
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    if (depositAmountInput) {
        depositAmountInput.addEventListener('input', function() {
            this.value = formatDepositAmount(this.value);
        });

        depositAmountInput.closest('form').addEventListener('submit', function() {
            depositAmountInput.value = depositAmountInput.value.replace(/\D/g, '');
        });

        document.querySelectorAll('.deposit-option').forEach(function(button) {
            button.addEventListener('click', function() {
                depositAmountInput.value = formatDepositAmount(this.dataset.amount);
            });
        });
    }
</script>
@endpush
