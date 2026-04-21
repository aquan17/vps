@extends('layouts.app')

@section('title', '500 - Lỗi hệ thống | CloudVPS')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="text-center py-5">
    <div style="font-size: 72px; line-height: 1; margin-bottom: 16px; opacity: .5;">⚠️</div>
    <h1 class="h2 fw-bold mb-2">500 — Lỗi hệ thống</h1>
    <p class="text-secondary mb-4" style="max-width: 440px; margin: 0 auto;">
        Đã xảy ra lỗi không mong muốn từ phía máy chủ.
        Chúng tôi đã ghi nhận sự cố và đang xử lý.
        Vui lòng thử lại sau vài phút.
    </p>
    <a href="{{ route('vps.dashboard') }}" class="btn btn-primary btn-lg fw-bold px-4">
        ← Quay về Dashboard
    </a>
</div>
@endsection
