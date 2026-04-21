@extends('layouts.app')

@section('title', 'Tài khoản cá nhân - CloudVPS')
@section('meta_description', 'Xem thông tin tài khoản CloudVPS, số dư hiện tại và đổi mật khẩu đăng nhập.')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Tài khoản cá nhân</h1>
        <p class="text-secondary mb-0">Quản lý hồ sơ, số dư và bảo mật tài khoản.</p>
    </div>
    <a href="{{ route('deposits.index') }}" class="btn btn-primary fw-semibold">💳 Nạp tiền</a>
</div>

<div class="profile-grid">
    <aside class="profile-summary-card">
        <div class="profile-avatar-wrap">
            <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        </div>

        <h2 class="profile-name">{{ $user->name }}</h2>
        <div class="profile-email">{{ $user->email }}</div>

        <div class="profile-stat-grid">
            <div class="profile-stat">
                <div class="profile-stat-icon blue">🖥</div>
                <div class="profile-stat-value">{{ $runningVpsCount }}</div>
                <div class="profile-stat-label">VPS chạy</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-icon cyan">📦</div>
                <div class="profile-stat-value">{{ $vpsCount }}</div>
                <div class="profile-stat-label">Tổng VPS</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-icon green">$</div>
                <div class="profile-stat-value small-value">{{ number_format($user->balance ?? 0) }}</div>
                <div class="profile-stat-label">Số dư VND</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-icon purple">🧾</div>
                <div class="profile-stat-value">{{ $depositCount }}</div>
                <div class="profile-stat-label">Lệnh nạp</div>
            </div>
        </div>

        <div class="profile-role-badge">
            {{ $user->is_admin ? '⭐ Quản trị viên' : '⭐ Thành viên CloudVPS' }}
        </div>
    </aside>

    <section class="profile-main">
        <div class="profile-panel">
            <div class="profile-panel-head">
                <div>
                    <h2>Thông tin tài khoản</h2>
                    <p>Các thông tin chính dùng để vận hành dịch vụ.</p>
                </div>
                <span class="profile-panel-icon">👤</span>
            </div>

            <div class="profile-info-grid">
                <div class="profile-info-item">
                    <span>Họ và tên</span>
                    <strong>{{ $user->name }}</strong>
                </div>
                <div class="profile-info-item">
                    <span>Email</span>
                    <strong>{{ $user->email }}</strong>
                </div>
                <div class="profile-info-item">
                    <span>Vai trò</span>
                    <strong>{{ $user->is_admin ? 'Quản trị viên' : 'Người dùng' }}</strong>
                </div>
                <div class="profile-info-item">
                    <span>Ngày tạo</span>
                    <strong>{{ $user->created_at ? $user->created_at->format('d/m/Y') : '-' }}</strong>
                </div>
                <div class="profile-info-item">
                    <span>Tổng đã nạp</span>
                    <strong class="text-success">{{ number_format($paidDepositTotal) }} VND</strong>
                </div>
                <div class="profile-info-item">
                    <span>Số dư hiện tại</span>
                    <strong class="text-success">{{ number_format($user->balance ?? 0) }} VND</strong>
                </div>
            </div>
        </div>

        <div class="profile-panel">
            <div class="profile-panel-head">
                <div>
                    <h2>Đổi mật khẩu</h2>
                    <p>Dùng mật khẩu mạnh tối thiểu 8 ký tự để bảo vệ tài khoản.</p>
                </div>
                <span class="profile-panel-icon lock">🔐</span>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" class="vstack gap-3">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="form-label fw-semibold">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" class="form-control form-control-lg @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Mật khẩu mới</label>
                        <input type="password" id="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" autocomplete="new-password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label fw-semibold">Nhập lại mật khẩu mới</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-lg" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="profile-note">
                    <span>i</span>
                    Sau khi đổi mật khẩu, phiên đăng nhập hiện tại vẫn được giữ và mã phiên sẽ được làm mới.
                </div>

                <div>
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-4 profile-submit-btn">Đổi mật khẩu</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .profile-grid {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }
    .profile-summary-card,
    .profile-panel {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 16px;
        box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .06);
    }
    .profile-summary-card {
        padding: 28px 22px;
        text-align: center;
        position: sticky;
        top: 16px;
    }
    .profile-avatar-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 16px;
    }
    .profile-avatar {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2563eb, #8b5cf6);
        color: #fff;
        font-size: 30px;
        font-weight: 800;
        box-shadow: 0 12px 28px rgba(37, 99, 235, .26);
        position: relative;
    }
    .profile-avatar::after {
        content: '✓';
        position: absolute;
        right: 2px;
        bottom: 4px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #10b981;
        color: #fff;
        border: 3px solid #fff;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .profile-name {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 4px;
    }
    .profile-email {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 22px;
        overflow-wrap: anywhere;
    }
    .profile-stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }
    .profile-stat {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 12px;
        padding: 14px 10px;
        text-align: left;
    }
    .profile-stat-icon {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-weight: 800;
    }
    .profile-stat-icon.blue { background: rgba(37, 99, 235, .1); color: #2563eb; }
    .profile-stat-icon.cyan { background: rgba(6, 182, 212, .12); color: #0891b2; }
    .profile-stat-icon.green { background: rgba(16, 185, 129, .12); color: #059669; }
    .profile-stat-icon.purple { background: rgba(139, 92, 246, .12); color: #7c3aed; }
    .profile-stat-value {
        font-family: 'Roboto Mono', monospace;
        font-size: 22px;
        font-weight: 800;
        line-height: 1.1;
    }
    .profile-stat-value.small-value {
        font-size: 15px;
    }
    .profile-stat-label {
        color: #64748b;
        font-size: 11px;
        margin-top: 3px;
    }
    .profile-role-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: rgba(139, 92, 246, .12);
        border: 1px solid rgba(139, 92, 246, .22);
        color: #7c3aed;
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 700;
        font-size: 13px;
    }
    .profile-main {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .profile-panel {
        padding: 24px;
    }
    .profile-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }
    .profile-panel-head h2 {
        font-size: 18px;
        font-weight: 800;
        margin: 0 0 4px;
    }
    .profile-panel-head p {
        color: #64748b;
        margin: 0;
        font-size: 13px;
    }
    .profile-panel-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 99, 235, .1);
        color: #2563eb;
        flex-shrink: 0;
    }
    .profile-panel-icon.lock {
        background: rgba(16, 185, 129, .12);
    }
    .profile-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .profile-info-item {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 12px;
        padding: 14px 16px;
        min-width: 0;
    }
    .profile-info-item span {
        display: block;
        color: #64748b;
        font-size: 12px;
        margin-bottom: 5px;
    }
    .profile-info-item strong {
        display: block;
        color: #0f172a;
        overflow-wrap: anywhere;
    }
    .profile-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: rgba(37, 99, 235, .08);
        border: 1px solid rgba(37, 99, 235, .16);
        border-radius: 12px;
        padding: 13px 14px;
        color: #475569;
        font-size: 13px;
    }
    .profile-note span {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #2563eb;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        flex-shrink: 0;
    }
    .profile-submit-btn {
        border-radius: 10px;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .22);
    }
    @media (max-width: 992px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
        .profile-summary-card {
            position: static;
        }
    }
    @media (max-width: 576px) {
        .profile-info-grid {
            grid-template-columns: 1fr;
        }
        .profile-panel {
            padding: 18px;
        }
    }
</style>
@endpush
