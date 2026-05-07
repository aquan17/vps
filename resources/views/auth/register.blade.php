@extends('layouts.app')

@section('title', 'Đăng ký CloudVPS - Tạo tài khoản thuê VPS')
@section('meta_description', 'Tạo tài khoản CloudVPS để thuê VPS Google Cloud, nạp tiền VietQR và quản lý máy chủ tự động trong vài phút.')

@if(config('services.turnstile.enabled') && filled(config('services.turnstile.site_key')))
    @push('head')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif

@section('content')
<div class="mb-6 text-center">
    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-2xl font-bold text-brand-700">CV</div>
    <h1 class="mb-2 text-2xl font-bold text-slate-950">Tạo tài khoản</h1>
    <p class="mb-0 text-sm text-slate-500">Bắt đầu thuê và quản lý VPS trong vài phút.</p>
</div>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div>
        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Họ và tên</label>
        <input
            id="name"
            type="text"
            class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus"
            name="name"
            value="{{ old('name') }}"
            required
            autofocus
            autocomplete="name"
            placeholder="Nguyễn Văn A"
        >
    </div>

    <div>
        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
        <input
            id="email"
            type="email"
            class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus"
            name="email"
            value="{{ old('email') }}"
            required
            autocomplete="email"
            placeholder="name@company.com"
        >
    </div>

    <div>
        <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Mật khẩu</label>
        <input
            id="password"
            type="password"
            class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus"
            name="password"
            required
            autocomplete="new-password"
            placeholder="Tối thiểu 8 ký tự"
        >
    </div>

    <div>
        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Xác nhận mật khẩu</label>
        <input
            id="password_confirmation"
            type="password"
            class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus"
            name="password_confirmation"
            required
            autocomplete="new-password"
            placeholder="Nhập lại mật khẩu"
        >
    </div>

    @if(config('services.turnstile.enabled') && filled(config('services.turnstile.site_key')))
        <div class="flex justify-center">
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
        </div>
    @endif

    <x-ui.button type="submit" size="lg" full-width>
        Đăng ký miễn phí
    </x-ui.button>
</form>

<p class="mb-0 mt-6 text-center text-sm text-slate-500">
    Đã có tài khoản?
    <a href="{{ route('login') }}" class="font-bold text-brand-700 no-underline hover:text-brand-600">Đăng nhập ngay</a>
</p>
@endsection
