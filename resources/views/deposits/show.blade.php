@extends('layouts.app')

@section('title', 'Thanh toán nạp tiền - CloudVPS')
@section('meta_description', 'Quét mã QR hoặc mở link PayOS để nạp tiền vào tài khoản CloudVPS.')
@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $payosData = (array) data_get($order->raw_payload, 'data', []);
        $payosCheckoutUrl = $payosData['checkoutUrl'] ?? null;
        $payosQrCode = $payosData['qrCode'] ?? null;
        $qrImage = $payosQrCode
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($payosQrCode)
            : $order->viet_qr_url;
    @endphp

    <x-ui.page-header title="Thanh toán nạp tiền" subtitle="Quét mã QR đúng số tiền, hệ thống sẽ tự cộng số dư sau khi PayOS xác nhận.">
        <x-ui.button :href="route('deposits.index')" variant="secondary">
            Quay lại
        </x-ui.button>
    </x-ui.page-header>

    <div
        class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]"
        id="depositPaymentPage"
        data-status-url="{{ route('deposits.status', $order->id) }}"
        data-is-pending="{{ $order->status === 'pending' ? '1' : '0' }}"
    >
        <x-ui.card padding="lg" class="text-center">
            @if($order->status === 'paid')
                <div class="deposit-success-mark">✓</div>
                <h2 class="mt-4 text-xl font-bold text-slate-950">Thanh toán thành công</h2>
                <p class="mx-auto mb-0 mt-2 max-w-xs text-sm text-slate-500">
                    Số dư đã được cộng vào tài khoản. Bạn có thể quay lại nạp tiền hoặc tạo VPS mới.
                </p>
                <div class="mt-5 grid gap-2">
                    <x-ui.button :href="route('vps.create')" variant="primary" full-width>Tạo VPS</x-ui.button>
                    <x-ui.button :href="route('deposits.index')" variant="secondary" full-width>Về trang nạp tiền</x-ui.button>
                </div>
            @else
                <img src="{{ $qrImage }}" alt="Mã QR nạp tiền CloudVPS"
                    class="mx-auto w-full max-w-xs rounded-card border border-slate-200 bg-white p-2">
                <p class="mb-0 mt-4 text-sm text-slate-500">QR động theo đúng số tiền và mã nạp.</p>

                @if($payosCheckoutUrl)
                    <a href="{{ $payosCheckoutUrl }}" target="_blank" rel="noopener" class="btn btn-primary mt-4 w-100 fw-bold">
                        Mở trang thanh toán PayOS
                    </a>
                @endif
            @endif
        </x-ui.card>

        <x-ui.card title="Thông tin thanh toán" subtitle="Không sửa số tiền hoặc nội dung chuyển khoản." padding="lg" class="deposit-card">
            <div class="divide-y divide-slate-200">
                @if($order->provider === 'payos')
                    <div class="flex items-start justify-between gap-4 py-3">
                        <span class="text-sm text-slate-500">Cổng thanh toán</span>
                        <strong class="text-right text-sm text-slate-950">PayOS</strong>
                    </div>
                @endif
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Ngân hàng</span>
                    <strong class="text-right text-sm text-slate-950">{{ $payosData['bin'] ?? config('deposit.bank_id') }}</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Số tài khoản</span>
                    <strong class="text-right font-mono text-sm text-slate-950">{{ $payosData['accountNumber'] ?? config('deposit.account_no') }}</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Chủ tài khoản</span>
                    <strong class="text-right text-sm text-slate-950">{{ $payosData['accountName'] ?? config('deposit.account_name') }}</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Số tiền</span>
                    <strong
                        class="text-right font-mono text-base text-brand-700">{{ number_format($order->amount, 0, ',', '.') }}
                        VND</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Nội dung</span>
                    <strong class="text-right font-mono text-base text-danger-700">{{ $order->code }}</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Trạng thái</span>
                    <span
                        id="depositStatusBadge"
                        class="deposit-live-badge {{ $order->status === 'paid' ? 'is-paid' : 'is-pending' }}"
                    >
                        {{ $order->status === 'paid' ? 'Đã thanh toán' : 'Chờ chuyển khoản' }}
                    </span>
                </div>
            </div>

            <div id="depositLiveMessage" class="mt-5 rounded-card border border-brand-100 bg-brand-50 p-4 text-sm leading-6 text-brand-700">
                @if($order->status === 'paid')
                    Thanh toán đã được xác nhận, số dư đã được cộng vào tài khoản.
                @else
                    Trang này sẽ tự kiểm tra trạng thái thanh toán. Sau khi PayOS xác nhận, số dư sẽ được cộng tự động.
                @endif
            </div>
        </x-ui.card>
    </div>
@endsection

@push('styles')
<style>
    .deposit-card > .border-b {
        padding: 16px 24px;
    }

    .deposit-success-mark {
        display: inline-flex;
        width: 92px;
        height: 92px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #dcfce7;
        color: #15803d;
        font-size: 46px;
        font-weight: 900;
        box-shadow: 0 16px 34px rgba(21, 128, 61, .16);
    }

    .deposit-live-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .deposit-live-badge.is-paid {
        background: #dcfce7;
        color: #15803d;
    }

    .deposit-live-badge.is-pending {
        background: #fef3c7;
        color: #b45309;
    }

    @media (max-width: 768px) {
        .deposit-card > .border-b {
            padding: 14px 18px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var page = document.getElementById('depositPaymentPage');
        if (!page || page.dataset.isPending !== '1') return;

        var statusUrl = page.dataset.statusUrl;
        var badge = document.getElementById('depositStatusBadge');
        var message = document.getElementById('depositLiveMessage');
        var attempts = 0;
        var maxAttempts = 80;

        function markPaid(data) {
            page.dataset.isPending = '0';

            if (badge) {
                badge.classList.remove('is-pending');
                badge.classList.add('is-paid');
                badge.textContent = 'Đã thanh toán';
            }

            if (message) {
                message.className = 'mt-5 rounded-card border border-success-100 bg-success-50 p-4 text-sm leading-6 text-success-700';
                message.textContent = 'Thanh toán đã được xác nhận, số dư hiện tại: ' + (data.balance || '');
            }

            var topbarBalance = document.getElementById('topbarBalance');
            if (topbarBalance && data.balance) {
                topbarBalance.textContent = data.balance;
            }
        }

        function pollStatus() {
            attempts++;

            fetch(statusUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.paid) {
                        markPaid(data);
                        return;
                    }

                    if (attempts < maxAttempts) {
                        setTimeout(pollStatus, 3000);
                    }
                })
                .catch(function () {
                    if (attempts < maxAttempts) {
                        setTimeout(pollStatus, 5000);
                    }
                });
        }

        setTimeout(pollStatus, 2500);
    })();
</script>
@endpush
