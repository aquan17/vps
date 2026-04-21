@extends('layouts.app')

@section('title', 'Quét mã VietQR - CloudVPS')
@section('meta_description', 'Quét mã VietQR để nạp tiền vào tài khoản CloudVPS với nội dung chuyển khoản tự động.')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-ui.page-header title="Quét mã VietQR" subtitle="Giữ nguyên nội dung chuyển khoản để hệ thống tự cộng tiền.">
        <x-ui.button :href="route('deposits.index')" variant="secondary">
            Quay lại
        </x-ui.button>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
        <x-ui.card padding="lg" class="text-center">
            <img src="{{ $order->viet_qr_url }}" alt="Mã VietQR nạp tiền CloudVPS"
                class="mx-auto w-full max-w-xs rounded-card border border-slate-200 bg-white p-2">
            <p class="mb-0 mt-4 text-sm text-slate-500">QR động theo đúng số tiền và mã nạp.</p>
        </x-ui.card>

        <x-ui.card title="Thông tin chuyển khoản" subtitle="Không sửa nội dung chuyển khoản." padding="lg" class="deposit-card">
            <div class="divide-y divide-slate-200">
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Ngân hàng</span>
                    <strong class="text-right text-sm text-slate-950">{{ config('deposit.bank_id') }}</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Số tài khoản</span>
                    <strong class="text-right font-mono text-sm text-slate-950">{{ config('deposit.account_no') }}</strong>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm text-slate-500">Chủ tài khoản</span>
                    <strong class="text-right text-sm text-slate-950">{{ config('deposit.account_name') }}</strong>
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
                    <x-ui.badge :variant="$order->status === 'paid' ? 'success' : 'warning'">
                        {{ $order->status === 'paid' ? 'Đã thanh toán' : 'Chờ chuyển khoản' }}
                    </x-ui.badge>
                </div>
            </div>

            <div class="mt-5 rounded-card border border-brand-100 bg-brand-50 p-4 text-sm leading-6 text-brand-700">
                Sau khi ngân hàng gửi giao dịch về hệ thống, số dư sẽ được cộng tự động nếu khớp đúng số tiền và nội dung.
            </div>
        </x-ui.card>
    </div>
@endsection

@push('styles')
<style>
    .deposit-card > .border-b {
        padding: 16px 24px;
    }

    @media (max-width: 768px) {
        .deposit-card > .border-b {
            padding: 14px 18px;
        }
    }
</style>
@endpush
