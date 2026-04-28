<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f172a">
    <title>@yield('title', 'CloudVPS - Thuê VPS tự động')</title>
    <meta name="description" content="@yield('meta_description', 'CloudVPS cung cấp VPS Google Cloud tự động, quản lý máy chủ, nạp tiền VietQR và triển khai nhanh cho người dùng Việt Nam.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="CloudVPS">
    <meta property="og:title" content="@yield('title', 'CloudVPS - Thuê VPS tự động')">
    <meta property="og:description" content="@yield('meta_description', 'CloudVPS cung cấp VPS Google Cloud tự động, quản lý máy chủ, nạp tiền VietQR và triển khai nhanh cho người dùng Việt Nam.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'CloudVPS - Thuê VPS tự động')">
    <meta name="twitter:description" content="@yield('meta_description', 'CloudVPS cung cấp VPS Google Cloud tự động, quản lý máy chủ, nạp tiền VietQR và triển khai nhanh cho người dùng Việt Nam.')">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
    <style>
        :root {
            --bg: #f1f5f9;
            --bg2: #ffffff;
            --bg3: #f8fafc;
            --nav-bg: #0f172a;
            --border: #e2e8f0;
            --accent: #3b82f6;
            --accent2: #06b6d4;
            --accent3: #8b5cf6;
            --green: #10b981;
            --yellow: #f59e0b;
            --red: #ef4444;
            --text: #0f172a;
            --text2: #475569;
            --text3: #94a3b8;
            --glow: 0 8px 20px rgba(59,130,246,0.3);
            --radius: 16px;
            --tab-h: 70px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        *, *::before, *::after { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        html, body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        /* ══════════════════════════════════════════
           DESKTOP: Sidebar Layout
        ══════════════════════════════════════════ */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ─── Sidebar (Desktop only) ─── */
        .sidebar {
            width: 260px;
            min-width: 260px;
            height: 100vh;
            max-height: 100vh;
            min-height: 0;
            background: var(--nav-bg);
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent2), var(--accent3));
        }

        .logo { padding: 22px 20px 18px; border-bottom: 1px solid rgba(255,255,255,0.06); text-decoration: none; display: block; flex-shrink: 0; }
        .logo-name {
            font-family: 'Roboto Mono', monospace;
            font-size: 20px; font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #06b6d4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .logo-sub { font-size: 11px; color: #64748b; letter-spacing: 2px; text-transform: uppercase; margin-top: 3px; font-family: 'Roboto Mono', monospace; }

        .sidebar-nav {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 2px;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(148,163,184,0.45) transparent;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.45); border-radius: 999px; }
        .nav-label { width: 100%; flex: 0 0 auto; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: #475569; padding: 12px 12px 4px; font-weight: 600; }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            flex: 0 0 auto;
            padding: 11px 13px; border-radius: 10px;
            cursor: pointer; color: #94a3b8;
            font-size: 14px; font-weight: 500;
            transition: all 0.18s; text-decoration: none;
            border: 1px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .nav-item span:not(.nav-icon) { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
        .nav-item:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }
        .nav-item.active { background: rgba(59,130,246,0.15); color: #60a5fa; border-color: rgba(59,130,246,0.2); }
        .nav-icon { font-size: 17px; width: 22px; text-align: center; }

        .sidebar-footer { padding: 12px 10px; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
        .user-info { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; }
        .avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent3));
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: white; flex-shrink: 0;
        }
        .user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; }

        /* ─── Main content area (Desktop) ─── */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }

        .topbar {
            height: 58px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 24px; gap: 12px;
            background: var(--bg2); flex-shrink: 0;
            justify-content: space-between;
        }
        .topbar-title { font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .topbar-subtitle { font-size: 13px; color: var(--text3); font-weight: 400; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .topbar-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px;
            font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.18s; text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: white; border: none; }
        .btn-primary:hover { background: #2563eb; box-shadow: var(--glow); }
        .btn-ghost { background: var(--bg3); color: var(--text2); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text); border-color: var(--accent); }

        .content { flex: 1; overflow-y: auto; padding: 24px; }
        .content::-webkit-scrollbar { width: 4px; }
        .content::-webkit-scrollbar-track { background: transparent; }
        .content::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* ══════════════════════════════════════════
           MOBILE OVERRIDES
        ══════════════════════════════════════════ */
        @media (max-width: 768px) {
            body { overflow: hidden; }

            .app-shell { flex-direction: column; height: 100dvh; }
            .sidebar { display: none; }

            /* Mobile top header */
            .topbar {
                height: 56px;
                padding: 0 16px;
                position: sticky; top: 0; z-index: 100;
            }
            .topbar-subtitle { display: none; }
            .topbar-title { font-size: 16px; font-weight: 700; }

            .content {
                flex: 1;
                padding: 16px 16px calc(var(--tab-h) + var(--safe-bottom) + 16px);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Bottom Tab Bar */
            .mobile-tabs {
                display: flex !important;
                position: fixed;
                bottom: 0; left: 0; right: 0;
                height: calc(var(--tab-h) + var(--safe-bottom));
                padding-bottom: var(--safe-bottom);
                background: var(--nav-bg);
                border-top: 1px solid rgba(255,255,255,0.08);
                z-index: 200;
            }
            .mobile-tab {
                flex: 1;
                display: flex; flex-direction: column;
                align-items: center; justify-content: center;
                gap: 4px;
                color: #64748b;
                text-decoration: none;
                font-size: 10px; font-weight: 600;
                transition: all 0.18s;
                padding: 8px 4px;
            }
            .mobile-tab .tab-icon { font-size: 22px; }
            .mobile-tab.active { color: var(--accent); }
            .mobile-tab.active .tab-icon { transform: scale(1.15); }
        }

        /* ──────────────────────────────────────────
           Mobile Tabs (hidden on desktop by default)
        ────────────────────────────────────────── */
        .mobile-tabs { display: none; }

        /* ══════════════════════════════════════════
           SHARED COMPONENTS
        ══════════════════════════════════════════ */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

        .stat-card {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 16px; position: relative;
            overflow: hidden; transition: all 0.2s;
        }
        .stat-card:hover { transform: translateY(-1px); }
        .stat-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
        .stat-card.blue::after { background: var(--accent); }
        .stat-card.cyan::after { background: var(--accent2); }
        .stat-card.purple::after { background: var(--accent3); }
        .stat-card.green::after { background: var(--green); }
        .stat-label { font-size: 11px; color: var(--text3); font-weight: 500; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 22px; font-weight: 700; font-family: 'Roboto Mono', monospace; margin-bottom: 4px; }
        .stat-change { font-size: 11px; color: var(--green); }
        .stat-icon { position: absolute; top: 14px; right: 14px; font-size: 20px; opacity: 0.12; }

        .table-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 20px; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .table-title { font-size: 15px; font-weight: 600; }
        .table-subtitle { font-size: 12px; color: var(--text3); margin-top: 3px; }
        .table-card table:not(.table) { width: 100%; border-collapse: collapse; }
        .table-card table:not(.table) th { text-align: left; padding: 12px 20px; font-size: 11px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: 0.8px; background: var(--bg3); border-bottom: 1px solid var(--border); font-family: 'Roboto Mono', monospace; }
        .table-card table:not(.table) td { padding: 14px 20px; font-size: 13px; border-bottom: 1px solid rgba(0,0,0,0.03); color: var(--text2); }
        .table-card table:not(.table) tr:last-child td { border-bottom: none; }
        .table-card table:not(.table) tr:hover td { background: rgba(0,0,0,0.015); }
        .td-main { color: var(--text); font-weight: 500; }

        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-badge::before { content: '●'; font-size: 7px; }
        .status-running { background: rgba(16,185,129,0.1); color: var(--green); }
        .status-stopped { background: rgba(239,68,68,0.1); color: var(--red); }
        .status-pending { background: rgba(245,158,11,0.1); color: var(--yellow); }

        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .section-title { font-size: 16px; font-weight: 700; }
        .section-sub { font-size: 12px; color: var(--text3); margin-top: 2px; }

        /* VPS Cards */
        .vps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
        @media (max-width: 640px) { .vps-grid { grid-template-columns: 1fr; } }

        .vps-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; transition: all 0.2s; }
        .vps-card:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-1px); }
        .vps-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
        .vps-name { font-size: 14px; font-weight: 700; color: var(--text); }
        .vps-ip { font-size: 11px; color: var(--text3); font-family: 'Roboto Mono', monospace; margin-top: 3px; }
        .vps-metrics { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 14px; }
        .vps-metric { text-align: center; }
        .metric-label { font-size: 9px; color: var(--text3); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .metric-val { font-size: 11px; font-weight: 700; font-family: 'Roboto Mono', monospace; }
        .vps-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--border); }
        .vps-expiry { font-size: 11px; color: var(--text3); }
        .vps-actions { display: flex; gap: 6px; }
        .vps-btn { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; transition: all 0.18s; }
        .vps-btn.restart { background: rgba(239,68,68,0.08); color: var(--red); }
        .vps-btn.manage { background: rgba(59,130,246,0.1); color: var(--accent); }

        /* Forms */
        .form-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 16px; }
        .form-title { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
        .form-sub { font-size: 12px; color: var(--text3); margin-bottom: 18px; }
        .form-group { margin-bottom: 16px; }
        .form-label { font-size: 11px; font-weight: 600; color: var(--text2); margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input {
            width: 100%; background: var(--bg3); border: 1px solid var(--border);
            border-radius: 10px; padding: 10px 14px; color: var(--text);
            font-size: 14px; font-family: 'Inter', sans-serif; outline: none; transition: all 0.18s;
        }
        .form-input:focus { border-color: var(--accent); background: var(--bg2); box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
        .form-submit {
            width: 100%; padding: 14px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, var(--accent), #2563eb); color: white;
            font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.2s;
        }
        .form-submit:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: var(--glow); }

        /* Auth layout */
        .auth-container { display: flex; justify-content: center; align-items: center; min-height: 100dvh; padding: 20px; width: 100vw; background: var(--bg); }
        .auth-card { width: 100%; max-width: 440px; background: var(--bg2); border: 1px solid var(--border); border-radius: 20px; padding: 36px 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.08); }
        @media (max-width: 480px) { .auth-card { padding: 28px 20px; border-radius: 16px; } }

        /* Animations */
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
    @stack('styles')
</head>
<body>

@auth
<div class="app-shell">

    {{-- ════════ SIDEBAR (Desktop) ════════ --}}
    <div class="sidebar">
        <a href="{{ url('/') }}" class="logo">
            <div class="logo-name">⚡ CloudVPS</div>
            <div class="logo-sub">Control Panel</div>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-label">Tổng quan</div>
            <a href="{{ route('vps.dashboard') }}" class="nav-item {{ request()->routeIs('vps.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🖥️</span> VPS của tôi
            </a>

            <div class="nav-label">Dịch vụ</div>
            <a href="{{ route('vps.create') }}" class="nav-item {{ request()->routeIs('vps.create') ? 'active' : '' }}">
                <span class="nav-icon">➕</span> Tạo VPS mới
            </a>

            <div class="nav-label">Tài khoản</div>
            <a href="{{ route('deposits.index') }}" class="nav-item {{ request()->routeIs('deposits.*') ? 'active' : '' }}"><span class="nav-icon">$</span> Nạp tiền</a>
            <a href="{{ route('profile.show') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}"><span class="nav-icon">👤</span> Tài khoản cá nhân</a>

            @if(Auth::user()->is_admin)
            <div class="nav-label" style="color:var(--red); margin-top:8px;">Quản trị</div>
            <a href="{{ route('admin.revenue') }}" class="nav-item {{ request()->routeIs('admin.revenue') ? 'active' : '' }}" style="border-color:rgba(239,68,68,0.15);">
                <span class="nav-icon">📊</span> Doanh thu
            </a>
            <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}" style="border-color:rgba(239,68,68,0.15);">
                <span class="nav-icon">👥</span> Người dùng
            </a>
            <a href="{{ route('admin.vouchers.index') }}" class="nav-item {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}" style="border-color:rgba(239,68,68,0.15);">
                <span class="nav-icon">%</span> Voucher
            </a>
            <a href="{{ route('admin.google-cloud') }}" class="nav-item {{ request()->routeIs('admin.google-cloud') ? 'active' : '' }}" style="border-color:rgba(239,68,68,0.15);">
                <span class="nav-icon">☁️</span> Google Cloud
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                <div style="min-width:0;">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:none; border:none; color:var(--red); font-size:11px; cursor:pointer; font-weight:600; font-family:'Roboto Mono',monospace; padding:0;">Đăng xuất ⍈</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ MAIN ════════ --}}
    <div class="main">

        {{-- Top Bar --}}
        <div class="topbar">
            <div class="topbar-title">
                @if(request()->routeIs('vps.dashboard')) 🖥️ VPS của tôi
                @elseif(request()->routeIs('vps.create')) ➕ Tạo VPS Mới
                @elseif(request()->routeIs('vps.show')) 📋 Chi tiết VPS
                @elseif(request()->routeIs('admin.revenue')) 📊 Doanh thu
                @elseif(request()->routeIs('admin.users*')) 👥 Người dùng
                @elseif(request()->routeIs('admin.google-cloud')) ☁️ 
                @elseif(request()->routeIs('deposits.*')) $ Nạp tiền
                @elseif(request()->routeIs('profile.*')) 👤 Tài khoản cá nhân
                @endif
                <span class="topbar-subtitle">
                    Xin chào, {{ Auth::user()->name }} 👋
                </span>
            </div>
            <div class="topbar-actions">
                <span id="topbarBalance" style="font-family:'Roboto Mono',monospace; font-size:12px; font-weight:700; color:var(--green); background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); padding:5px 10px; border-radius:7px;">{{ number_format(Auth::user()->balance ?? 0) }} VND</span>
                <a href="{{ route('vps.create') }}" class="topbar-btn btn-primary" style="text-decoration:none; padding:7px 12px; font-size:12px;">+ Mua VPS</a>
            </div>
        </div>

        {{-- Page Content --}}
        <div class="content">

            {{-- Toasts --}}
            @if(session('success'))
            <div style="background:var(--bg2); border-left:4px solid var(--green); border-radius:12px; padding:14px 18px; margin-bottom:16px; display:flex; align-items:center; gap:12px; animation:slideUp 0.3s ease; box-shadow:0 4px 15px rgba(0,0,0,0.06);">
                <span style="color:var(--green); font-size:18px; flex-shrink:0;">✓</span>
                <span style="font-size:13px; font-weight:500;">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error') || $errors->any())
            <div style="background:#fff5f5; border:1px solid #fecaca; border-left:4px solid #ef4444; border-radius:8px; padding:12px 14px; margin-bottom:16px; display:flex; align-items:flex-start; gap:10px; animation:slideUp 0.3s ease; box-shadow:0 6px 18px rgba(239,68,68,0.08); color:#991b1b;">
                <span style="color:var(--red); font-size:18px; line-height:1.2; flex-shrink:0;">⚠</span>
                <div>
                    <div style="font-size:13px; font-weight:700; margin-bottom:4px;">Có lỗi xảy ra:</div>
                    <div style="font-size:13px; line-height:1.5; color:#7f1d1d;">
                        @if(session('error'))<div>{{ session('error') }}</div>@endif
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                </div>
            </div>
            @endif

            @yield('content')
        </div>
    </div>

    {{-- ════════ MOBILE BOTTOM TABS ════════ --}}
    <nav class="mobile-tabs">
        <a href="{{ route('vps.dashboard') }}" class="mobile-tab {{ request()->routeIs('vps.dashboard') ? 'active' : '' }}">
            <span class="tab-icon">🖥️</span>
            <span>VPS Của Tôi</span>
        </a>
        <a href="{{ route('vps.create') }}" class="mobile-tab {{ request()->routeIs('vps.create') ? 'active' : '' }}">
            <span class="tab-icon">➕</span>
            <span>Tạo Mới</span>
        </a>
        <a href="{{ route('deposits.index') }}" class="mobile-tab {{ request()->routeIs('deposits.*') ? 'active' : '' }}">
            <span class="tab-icon">💳</span>
            <span>Nạp Tiền</span>
        </a>
        @if(Auth::user()->is_admin)
        <a href="{{ route('admin.google-cloud') }}" class="mobile-tab {{ request()->routeIs('admin.google-cloud') ? 'active' : '' }}">
            <span class="tab-icon">☁️</span>
            <span>GCloud</span>
        </a>
        @endif
        <a href="#" class="mobile-tab" onclick="document.getElementById('profileSheet').classList.toggle('open'); return false;">
            <span class="tab-icon">👤</span>
            <span>Tài khoản</span>
        </a>
    </nav>

    {{-- ════════ PROFILE BOTTOM SHEET ════════ --}}
    <div id="profileSheet" style="display:none;" onclick="if(event.target===this) this.classList.remove('open');">
        <div class="sheet-body">
            <div style="width:40px; height:4px; background:var(--border); border-radius:2px; margin:0 auto 20px; flex-shrink:0;"></div>
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid var(--border);">
                <div class="avatar" style="width:48px; height:48px; font-size:18px;">{{ substr(Auth::user()->name, 0, 1) }}</div>
                <div>
                    <div style="font-weight:700; color:var(--text);">{{ Auth::user()->name }}</div>
                    <div style="font-size:12px; color:var(--text3);">{{ Auth::user()->email }}</div>
                    @if(Auth::user()->is_admin)
                    <div style="font-size:10px; font-weight:700; background:rgba(239,68,68,0.1); color:var(--red); padding:2px 8px; border-radius:10px; display:inline-block; margin-top:4px;">ADMIN</div>
                    @endif
                </div>
            </div>
            <a href="{{ route('profile.show') }}" class="sheet-item">👤 Tài khoản cá nhân</a>
            <a href="{{ route('deposits.index') }}" class="sheet-item">$ Nạp tiền</a>
            @if(Auth::user()->is_admin)
            <a href="{{ route('admin.revenue') }}" class="sheet-item">📊 Doanh thu</a>
            <a href="{{ route('admin.users') }}" class="sheet-item">👥 Người dùng</a>
            <a href="{{ route('admin.google-cloud') }}" class="sheet-item">☁️ Google Cloud</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="sheet-item" style="width:100%; text-align:left; border:none; cursor:pointer; background:none; color:var(--red); font-family:'Inter',sans-serif;">⍈ Đăng xuất</button>
            </form>
        </div>
    </div>

</div>
@endauth

@guest
<div class="auth-container">
    <div class="auth-card">
        @if(session('error') || $errors->any())
            <div class="alert alert-danger mb-4" role="alert">
                @if(session('error'))
                    <div>{{ session('error') }}</div>
                @endif

                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</div>
@endguest

<style>
/* Profile Bottom Sheet */
#profileSheet {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 300;
    display: none;
    align-items: flex-end;
}
#profileSheet.open { display: flex !important; }
.sheet-body {
    background: var(--bg2);
    border-radius: 20px 20px 0 0;
    padding: 16px 20px calc(var(--tab-h) + var(--safe-bottom) + 8px);
    width: 100%;
    animation: slideUp 0.3s ease;
}
.sheet-item {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 4px; font-size: 15px; font-weight: 500;
    border-bottom: 1px solid var(--border); text-decoration: none;
    color: var(--text); transition: opacity 0.15s;
}
.sheet-item:last-child { border-bottom: none; }
.sheet-item:active { opacity: 0.6; }
</style>

<x-support.zalo-button :mobile-offset="true" />

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="{{ asset('js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
