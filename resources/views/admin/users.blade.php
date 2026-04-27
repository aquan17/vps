@extends('layouts.app')

@section('title', 'Quản lý người dùng - CloudVPS')
@section('meta_description', 'Quản lý tài khoản người dùng, số dư ví, lịch sử nạp và VPS đang thuê.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $money = function ($value) {
        return number_format((int) $value, 0, ',', '.') . ' VND';
    };
@endphp

<x-ui.page-header
    eyebrow="Quản trị"
    title="Người dùng"
    subtitle="Tìm tài khoản và sửa số dư ví cho khách hàng."
>
    <x-ui.button :href="route('admin.revenue')" variant="outline">Xem doanh thu</x-ui.button>
</x-ui.page-header>

<div class="admin-users-page space-y-4">
    <div class="admin-users-stats">
        <div class="admin-users-stat">
            <span>User</span>
            <strong>{{ number_format($stats['users']) }}</strong>
        </div>
        <div class="admin-users-stat">
            <span>Số dư ví</span>
            <strong>{{ $money($stats['balance']) }}</strong>
        </div>
        <div class="admin-users-stat">
            <span>VPS</span>
            <strong>{{ number_format($stats['vps']) }}</strong>
        </div>
        <div class="admin-users-stat">
            <span>Đã nạp</span>
            <strong>{{ $money($stats['paid']) }}</strong>
        </div>
    </div>

    <x-ui.card class="admin-users-card overflow-hidden" padding="none">
        <x-slot name="header">
            <form method="GET" action="{{ route('admin.users') }}" class="admin-users-search">
                <div>
                    <h2 class="mb-0.5 text-base font-bold text-slate-950">Danh sách người dùng</h2>
                    <p class="mb-0 text-xs text-slate-500">{{ $users->total() }} tài khoản phù hợp.</p>
                </div>
                <div class="admin-users-search-control">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Tìm theo ID, tên hoặc email"
                    >
                    <button type="submit" class="btn btn-primary fw-bold">Tìm</button>
                    @if($search !== '')
                        <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary fw-bold">Xóa</a>
                    @endif
                </div>
            </form>
        </x-slot>

        <div class="admin-users-list">
            @forelse($users as $user)
                <article class="admin-user-row">
                    <div class="admin-user-main">
                        <div class="admin-user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        <div class="admin-user-info">
                            <div class="admin-user-name">
                                {{ $user->name }}
                                @if($user->is_admin)
                                    <span class="admin-user-role">Admin</span>
                                @endif
                            </div>
                            <div class="admin-user-email">#{{ $user->id }} · {{ $user->email }}</div>
                            <div class="admin-user-meta">
                                <span>{{ number_format($user->vps_count) }} VPS</span>
                                <span>{{ number_format($user->running_vps_count) }} đang chạy</span>
                                <span>{{ number_format($user->deposit_count) }} lệnh nạp</span>
                                <span>Đã nạp {{ $money($user->paid_deposit_total ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="admin-user-balance">
                        <span>Số dư</span>
                        <strong>{{ $money($user->balance ?? 0) }}</strong>
                    </div>

                    <form method="POST" action="{{ route('admin.users.balance', $user) }}" class="admin-balance-form">
                        @csrf
                        @method('PATCH')
                        <input
                            type="text"
                            name="amount"
                            class="form-control admin-money-input"
                            inputmode="numeric"
                            value="{{ number_format((int) ($user->balance ?? 0), 0, ',', '.') }}"
                            required
                        >
                        <button type="submit" class="btn btn-primary fw-bold">Lưu</button>
                    </form>
                </article>
            @empty
                <div class="admin-users-empty">Không tìm thấy người dùng nào.</div>
            @endforelse
        </div>

        @if($users->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection

@push('styles')
<style>
    .admin-users-search {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .admin-users-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .admin-users-stat {
        display: flex;
        min-height: 74px;
        flex-direction: column;
        justify-content: center;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        padding: 14px 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
    }

    .admin-users-stat span {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .admin-users-stat strong {
        display: block;
        margin-top: 6px;
        color: #0f172a;
        font-family: 'Roboto Mono', monospace;
        font-size: 20px;
        font-weight: 900;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .admin-users-search-control {
        display: flex;
        min-width: min(520px, 100%);
        gap: 8px;
    }

    .admin-users-list {
        display: flex;
        flex-direction: column;
    }

    .admin-user-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 170px 420px;
        gap: 16px;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 20px;
    }

    .admin-user-row:last-child {
        border-bottom: 0;
    }

    .admin-user-main {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 12px;
    }

    .admin-user-avatar {
        display: flex;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        color: #fff;
        font-weight: 900;
    }

    .admin-user-info {
        min-width: 0;
    }

    .admin-user-name {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
    }

    .admin-user-role {
        border-radius: 999px;
        background: #fee2e2;
        color: #b91c1c;
        padding: 3px 8px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .admin-user-email {
        margin-top: 2px;
        overflow: hidden;
        color: #64748b;
        font-size: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-user-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .admin-user-meta span {
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 700;
    }

    .admin-user-balance span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .admin-user-balance strong {
        display: block;
        margin-top: 3px;
        color: #15803d;
        font-family: 'Roboto Mono', monospace;
        font-size: 14px;
        font-weight: 900;
    }

    .admin-balance-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 86px;
        gap: 8px;
    }

    .admin-money-input {
        font-family: 'Roboto Mono', monospace;
        font-weight: 800;
    }

    .admin-users-empty {
        padding: 40px 20px;
        text-align: center;
        color: #64748b;
        font-size: 14px;
    }

    @media (max-width: 1180px) {
        .admin-users-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .admin-user-row {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .admin-balance-form {
            grid-template-columns: 140px minmax(0, 1fr) 120px;
        }
    }

    @media (max-width: 768px) {
        .admin-users-stats {
            grid-template-columns: 1fr;
        }

        .admin-users-search {
            align-items: stretch;
            flex-direction: column;
        }

        .admin-users-search-control,
        .admin-balance-form {
            grid-template-columns: 1fr;
            display: grid;
        }

        .admin-user-row {
            padding: 14px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.querySelectorAll('.admin-money-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const digits = this.value.replace(/\D/g, '');
            this.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });
</script>
@endpush
