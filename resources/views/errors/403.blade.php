@extends('layouts.app')

@section('title', '403 - Không có quyền truy cập | CloudVPS')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="text-center py-5">
    <div style="font-size: 72px; line-height: 1; margin-bottom: 16px; opacity: .5;">🚫</div>
    <h1 class="h2 fw-bold mb-2">403 — Không có quyền truy cập</h1>
    <p class="text-secondary mb-4" style="max-width: 400px; margin: 0 auto;">
        Bạn không có quyền xem trang này.
        Nếu bạn cho rằng đây là lỗi, vui lòng liên hệ quản trị viên.
    </p>
    <a href="{{ route('vps.dashboard') }}" class="btn btn-primary btn-lg fw-bold px-4">
        ← Quay về Dashboard
    </a>
</div>
@endsection
