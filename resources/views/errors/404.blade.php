@extends('layouts.app')

@section('title', '404 - Không tìm thấy trang | CloudVPS')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="text-center py-5">
    <div style="font-size: 72px; line-height: 1; margin-bottom: 16px; opacity: .5;">🔍</div>
    <h1 class="h2 fw-bold mb-2">404 — Không tìm thấy trang</h1>
    <p class="text-secondary mb-4" style="max-width: 400px; margin: 0 auto;">
        Trang bạn đang tìm không tồn tại hoặc đã bị xóa.
        Có thể đường dẫn bị sai hoặc trang đã được chuyển đi.
    </p>
    <a href="{{ route('vps.dashboard') }}" class="btn btn-primary btn-lg fw-bold px-4">
        ← Quay về Dashboard
    </a>
</div>
@endsection
