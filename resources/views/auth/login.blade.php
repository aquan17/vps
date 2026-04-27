@extends('layouts.app')

@section('title', 'Đăng nhập CloudVPS - Quản lý VPS tự động')
@section('meta_description', 'Đăng nhập CloudVPS để quản lý VPS Google Cloud, theo dõi máy chủ, nạp tiền VietQR và vận hành dịch vụ nhanh chóng.')

@section('content')
<div class="mb-6 text-center">
    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-2xl font-bold text-brand-700">CV</div>
    <h1 class="mb-2 text-2xl font-bold text-slate-950">Đăng nhập</h1>
    <p class="mb-0 text-sm text-slate-500">CloudVPS Control Panel</p>
</div>

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
        <input
            id="email"
            type="email"
            class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
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
            autocomplete="current-password"
            placeholder="Nhập mật khẩu"
        >
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <label for="remember" class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
            <input
                id="remember"
                type="checkbox"
                name="remember"
                value="1"
                style="appearance: auto; -webkit-appearance: checkbox; width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;"
            >
            Duy trì đăng nhập
        </label>

        @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-700 no-underline hover:text-brand-600">Quên mật khẩu?</a>
        @endif
    </div>

    <x-ui.button type="submit" size="lg" full-width>
        Đăng nhập
    </x-ui.button>
</form>

<p class="mb-0 mt-6 text-center text-sm text-slate-500">
    Chưa có tài khoản?
    <a href="{{ route('register') }}" class="font-bold text-brand-700 no-underline hover:text-brand-600">Đăng ký ngay</a>
</p>
@endsection
