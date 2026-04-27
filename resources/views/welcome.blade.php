<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CloudVPS - Thuê VPS Google Cloud tự động</title>
    <meta name="description" content="Thuê VPS Google Cloud tự động, nạp tiền VietQR, nhận IP nhanh và quản lý máy chủ dễ dàng cho người dùng Việt Nam.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f7f8fc;
            --white: #ffffff;
            --ink: #0f1923;
            --mid: #4b5767;
            --soft: #8b95a2;
            --line: #e4e9f0;
            --line2: #edf0f5;
            --blue: #2563eb;
            --blue-light: #eff4ff;
            --blue-mid: #3b82f6;
            --blue-glow: rgba(37,99,235,.12);
            --teal: #0ea5e9;
            --violet: #7c3aed;
            --green: #10b981;
            --amber: #f59e0b;
            --red: #ef4444;
            --shadow-sm: 0 1px 3px rgba(15,25,35,.06), 0 1px 2px rgba(15,25,35,.04);
            --shadow-md: 0 4px 16px rgba(15,25,35,.08), 0 2px 6px rgba(15,25,35,.04);
            --shadow-lg: 0 12px 40px rgba(15,25,35,.10), 0 4px 12px rgba(15,25,35,.06);
            --shadow-xl: 0 24px 64px rgba(15,25,35,.12);
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        /* NAV */
        nav {
            position: fixed;
            inset: 0 0 auto;
            z-index: 100;
            height: 68px;
            display: flex;
            align-items: center;
            background: rgba(247,248,252,.88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--line);
        }

        .nav-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 32px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .logo { display: flex; align-items: center; gap: 10px; }

        .logo-mark {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--blue) 0%, var(--teal) 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px; font-weight: 800;
            box-shadow: 0 4px 12px rgba(37,99,235,.3);
        }

        .logo-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 19px; font-weight: 800;
            color: var(--ink); letter-spacing: -.3px;
        }

        .nav-menu { display: flex; gap: 4px; }

        .nav-menu a {
            padding: 7px 14px; border-radius: 8px;
            font-size: 14px; font-weight: 600;
            color: var(--mid); transition: all .18s;
        }

        .nav-menu a:hover { background: var(--white); color: var(--ink); box-shadow: var(--shadow-sm); }

        .nav-actions { display: flex; gap: 8px; }

        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 20px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all .2s;
            border: 1.5px solid transparent; white-space: nowrap;
        }

        .btn-outline { border-color: var(--line); color: var(--mid); background: var(--white); box-shadow: var(--shadow-sm); }
        .btn-outline:hover { border-color: #c8d4e4; color: var(--ink); box-shadow: var(--shadow-md); }

        .btn-blue { background: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,.3); }
        .btn-blue:hover { background: #1d55d6; box-shadow: 0 6px 20px rgba(37,99,235,.4); transform: translateY(-1px); }

        .btn-lg { padding: 14px 28px; font-size: 15px; border-radius: 12px; }

        /* HERO */
        .hero {
            padding: 130px 32px 90px;
            max-width: 1180px; margin: 0 auto;
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 60px; align-items: center; min-height: 100vh;
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px 6px 8px;
            background: var(--blue-light);
            border: 1px solid rgba(37,99,235,.18);
            border-radius: 99px;
            font-size: 13px; font-weight: 700; color: var(--blue);
            margin-bottom: 28px;
        }

        .badge-dot {
            width: 22px; height: 22px; background: var(--blue);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 11px; color: #fff;
        }

        .hero h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(40px, 4.5vw, 58px);
            line-height: 1.16; font-weight: 800;
            color: var(--ink); letter-spacing: -0.6px; margin-bottom: 22px;
        }

        .hero h1 .hl {
            background: linear-gradient(90deg, var(--blue) 0%, var(--teal) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }

        .hero-desc { font-size: 17px; color: var(--mid); line-height: 1.7; max-width: 480px; margin-bottom: 36px; }

        .hero-btns { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 48px; }

        .hero-trust { display: flex; align-items: center; gap: 14px; }

        .trust-avatars { display: flex; }
        .trust-avatars span {
            width: 32px; height: 32px; border-radius: 50%;
            border: 2.5px solid var(--white);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff; margin-left: -8px;
        }
        .trust-avatars span:first-child { margin-left: 0; background: #6366f1; }
        .trust-avatars span:nth-child(2) { background: #ec4899; }
        .trust-avatars span:nth-child(3) { background: var(--blue); }
        .trust-avatars span:nth-child(4) { background: var(--green); }

        .trust-text { font-size: 13px; color: var(--soft); font-weight: 600; }
        .trust-text strong { color: var(--mid); }

        /* HERO MOCKUP */
        .hero-right { position: relative; }

        .hero-card {
            background: var(--white); border: 1px solid var(--line);
            border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-xl);
        }

        .card-topbar {
            background: var(--bg); border-bottom: 1px solid var(--line);
            padding: 14px 20px; display: flex; align-items: center; gap: 10px;
        }

        .dot { width: 11px; height: 11px; border-radius: 50%; }
        .dot.red { background: #fc5a58; }
        .dot.yellow { background: #fdbc40; }
        .dot.green2 { background: #34c84a; }

        .card-url {
            flex: 1; background: var(--white); border: 1px solid var(--line);
            border-radius: 6px; padding: 5px 12px;
            font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--soft);
        }

        .card-body { padding: 24px; }

        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

        .dash-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 15px; font-weight: 800; color: var(--ink); }

        .dash-new { background: var(--blue); color: #fff; font-size: 11px; font-weight: 800; padding: 5px 12px; border-radius: 7px; }

        .vps-item {
            display: flex; align-items: center; gap: 12px; padding: 12px;
            border-radius: 10px; background: var(--bg); margin-bottom: 10px;
            border: 1px solid var(--line2);
        }

        .vps-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .vi-blue { background: var(--blue-light); }
        .vi-teal { background: #ecfeff; }
        .vi-violet { background: #f5f3ff; }

        .vps-info { flex: 1; min-width: 0; }
        .vps-name { font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 2px; }
        .vps-ip { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--soft); }

        .vps-status { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; }
        .status-dot.on { background: var(--green); box-shadow: 0 0 6px var(--green); animation: blink 2s ease infinite; }
        .status-dot.off { background: var(--soft); }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }
        .status-on { color: var(--green); }
        .status-off { color: var(--soft); }

        .dash-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 14px; }

        .mini-stat { background: var(--bg); border: 1px solid var(--line2); border-radius: 10px; padding: 12px; text-align: center; }
        .mini-val { font-family: 'JetBrains Mono', monospace; font-size: 18px; font-weight: 500; color: var(--ink); margin-bottom: 3px; }
        .mini-val.blue { color: var(--blue); }
        .mini-val.green { color: var(--green); }
        .mini-lbl { font-size: 10px; font-weight: 700; color: var(--soft); text-transform: uppercase; letter-spacing: .5px; }

        .float-badge {
            position: absolute; background: var(--white); border: 1px solid var(--line);
            border-radius: 12px; padding: 10px 14px; box-shadow: var(--shadow-lg);
            display: flex; align-items: center; gap: 9px;
            font-size: 12px; font-weight: 700; color: var(--ink);
            animation: floatY 3.5s ease-in-out infinite;
        }
        @keyframes floatY { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
        .float-badge.top-right { top: -16px; right: -16px; animation-delay: .4s; }
        .float-badge.bot-left { bottom: 20px; left: -20px; animation-delay: 1.2s; }
        .fb-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .fb-green { background: #ecfdf5; }
        .fb-blue { background: var(--blue-light); }

        /* STATS BAR */
        .stats-bar { background: var(--ink); padding: 32px; }
        .stats-bar-inner {
            max-width: 1180px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(4, 1fr);
        }

        .sbar-item {
            padding: 0 32px; border-right: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: 14px;
        }
        .sbar-item:first-child { padding-left: 0; }
        .sbar-item:last-child { border-right: none; }

        .sbar-icon { width: 44px; height: 44px; border-radius: 10px; background: rgba(255,255,255,.07); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .sbar-val { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 4px; }
        .sbar-lbl { font-size: 13px; color: rgba(255,255,255,.45); font-weight: 500; }

        /* FEATURES */
        .features-section { padding: 100px 32px; max-width: 1180px; margin: 0 auto; }

        .section-eyebrow { display: inline-block; font-size: 12px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--blue); margin-bottom: 14px; }

        .section-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(32px, 4vw, 48px); font-weight: 800; color: var(--ink); letter-spacing: -0.4px; line-height: 1.18; margin-bottom: 16px; }

        .section-sub { font-size: 17px; color: var(--mid); max-width: 500px; line-height: 1.7; margin-bottom: 56px; }

        .feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

        .feat-card {
            background: var(--white); border: 1px solid var(--line);
            border-radius: 18px; padding: 32px 28px;
            box-shadow: var(--shadow-sm);
            transition: box-shadow .25s, transform .25s, border-color .25s;
        }
        .feat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); border-color: transparent; }

        .feat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 22px; }
        .fi-blue { background: var(--blue-light); }
        .fi-teal { background: #ecfeff; }
        .fi-violet { background: #f5f3ff; }
        .fi-amber { background: #fffbeb; }
        .fi-green { background: #ecfdf5; }
        .fi-red { background: #fef2f2; }

        .feat-card h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 10px; letter-spacing: 0; }
        .feat-card p { font-size: 14px; color: var(--mid); line-height: 1.7; }

        /* PRICING */
        .pricing-section { background: var(--bg); padding: 100px 32px; }
        .pricing-inner { max-width: 1180px; margin: 0 auto; }

        .plans-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

        .plan {
            background: var(--white); border: 1.5px solid var(--line);
            border-radius: 20px; padding: 32px; box-shadow: var(--shadow-sm);
            transition: box-shadow .25s, transform .25s;
            display: flex; flex-direction: column;
        }
        .plan:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); }

        .plan.highlight {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px var(--blue-glow), var(--shadow-lg);
            position: relative;
        }

        .plan-badge {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(90deg, var(--blue), var(--teal));
            color: #fff; font-size: 11px; font-weight: 800;
            letter-spacing: 1.2px; text-transform: uppercase;
            padding: 5px 16px; border-radius: 99px; white-space: nowrap;
            box-shadow: 0 4px 12px rgba(37,99,235,.3);
        }

        .plan-tier { font-size: 12px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: var(--soft); margin-bottom: 10px; }
        .plan-name { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 800; color: var(--ink); margin-bottom: 8px; letter-spacing: 0; }
        .plan-tagline { font-size: 14px; color: var(--mid); margin-bottom: 28px; line-height: 1.5; }

        .plan-price-block { margin-bottom: 28px; }
        .plan-price { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 46px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; line-height: 1; }
        .plan.highlight .plan-price { color: var(--blue); }
        .plan-period { font-size: 14px; color: var(--soft); font-weight: 600; margin-left: 4px; }

        .plan-divider { height: 1px; background: var(--line); margin-bottom: 22px; }

        .plan-specs { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; flex: 1; }

        .plan-spec { display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .spec-k { color: var(--mid); font-weight: 500; display: flex; align-items: center; gap: 7px; }
        .spec-k::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--line); flex-shrink: 0; }
        .spec-v { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 500; color: var(--ink); background: var(--bg); padding: 3px 10px; border-radius: 6px; }
        .plan.highlight .spec-v { background: var(--blue-light); color: var(--blue); }

        .plan-cta { display: block; text-align: center; padding: 14px; border-radius: 11px; font-size: 15px; font-weight: 800; border: 1.5px solid var(--line); color: var(--mid); transition: all .2s; }
        .plan-cta:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-light); }
        .plan.highlight .plan-cta { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,.3); }
        .plan.highlight .plan-cta:hover { background: #1d55d6; box-shadow: 0 6px 20px rgba(37,99,235,.4); transform: translateY(-1px); }

        /* STEPS */
        .steps-section { padding: 100px 32px; max-width: 1180px; margin: 0 auto; }

        .steps-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; position: relative; }
        .steps-row::before { content: ''; position: absolute; top: 36px; left: 16%; width: 68%; height: 1.5px; background: linear-gradient(90deg, var(--blue-light), var(--teal) 50%, var(--blue-light)); }

        .step { padding: 0 24px; text-align: center; }
        .step-circle { width: 72px; height: 72px; border-radius: 50%; background: var(--white); border: 2px solid var(--blue); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 800; color: var(--blue); box-shadow: 0 0 0 6px var(--blue-glow); position: relative; z-index: 2; }
        .step h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 20px; font-weight: 800; color: var(--ink); margin-bottom: 10px; letter-spacing: 0; }
        .step p { font-size: 14px; color: var(--mid); line-height: 1.7; max-width: 260px; margin: 0 auto; }

        /* CTA */
        .cta-section {
            margin: 0 32px 80px; border-radius: 24px;
            background: linear-gradient(135deg, var(--ink) 0%, #1e3a5f 100%);
            padding: 72px 60px; display: flex; align-items: center;
            justify-content: space-between; gap: 40px; flex-wrap: wrap;
            overflow: hidden; position: relative;
        }
        .cta-section::before { content: ''; position: absolute; top: -80px; right: -80px; width: 320px; height: 320px; background: radial-gradient(circle, rgba(37,99,235,.35) 0%, transparent 70%); pointer-events: none; }
        .cta-section::after { content: ''; position: absolute; bottom: -60px; left: 30%; width: 240px; height: 240px; background: radial-gradient(circle, rgba(14,165,233,.2) 0%, transparent 70%); pointer-events: none; }

        .cta-text { position: relative; z-index: 2; }
        .cta-text h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(30px, 4vw, 44px); font-weight: 800; color: #fff; letter-spacing: -0.4px; line-height: 1.18; margin-bottom: 10px; }
        .cta-text p { color: rgba(255,255,255,.6); font-size: 16px; }

        .cta-btns { display: flex; gap: 12px; flex-wrap: wrap; position: relative; z-index: 2; }

        .btn-white { background: #fff; color: var(--blue); border-color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.2); }
        .btn-white:hover { background: #f0f6ff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,.25); }

        .btn-outline-white { border-color: rgba(255,255,255,.3); color: rgba(255,255,255,.85); background: rgba(255,255,255,.08); }
        .btn-outline-white:hover { border-color: rgba(255,255,255,.6); color: #fff; background: rgba(255,255,255,.14); }

        /* FOOTER */
        footer { border-top: 1px solid var(--line); padding: 28px 32px; max-width: 1180px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .footer-copy { font-size: 14px; color: var(--soft); font-weight: 500; }

        /* ANIMATIONS */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .65s ease, transform .65s ease; }
        .reveal.visible { opacity: 1; transform: none; }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .hero { grid-template-columns: 1fr; min-height: auto; padding-top: 110px; }
            .hero-right { display: none; }
            .feat-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-bar-inner { grid-template-columns: repeat(2, 1fr); }
            .sbar-item { padding: 20px 24px; border-right: none; border-bottom: 1px solid rgba(255,255,255,.1); }
        }

        @media (max-width: 768px) {
            .nav-inner { padding: 0 16px; }
            .nav-menu { display: none; }
            .nav-actions .btn { padding: 8px 14px; font-size: 13px; }
            
            .hero { padding: 110px 16px 60px; }
            .hero-left { text-align: center; }
            .hero-badge { margin: 0 auto 24px; display: inline-flex; }
            .hero h1 { font-size: clamp(32px, 8vw, 40px); line-height: 1.25; margin-bottom: 16px; }
            .hero-desc { font-size: 15px; margin: 0 auto 32px; text-align: center; }
            .hero-btns { flex-direction: column; gap: 12px; width: 100%; margin-bottom: 36px; }
            .hero-btns .btn-lg { width: 100%; justify-content: center; }
            .hero-trust { justify-content: center; }
            
            .stats-bar { padding: 8px 16px; }
            .stats-bar-inner { grid-template-columns: 1fr; }
            .sbar-item { padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,.1); }
            .sbar-item:last-child { border-bottom: none; }
            
            .features-section, .steps-section, .pricing-section { padding: 60px 16px; }
            .section-eyebrow { text-align: center; display: block; }
            .section-title { font-size: 28px; line-height: 1.3; text-align: center; }
            .section-sub { font-size: 15px; margin: 0 auto 40px; text-align: center; }
            
            .feat-grid { grid-template-columns: 1fr; gap: 16px; }
            .feat-card { padding: 24px 20px; }
            
            .plans-row { grid-template-columns: 1fr; max-width: 100%; gap: 20px; }
            .plan { padding: 24px 20px; }
            
            .steps-row { grid-template-columns: 1fr; gap: 32px; }
            .steps-row::before { display: none; }
            .step { padding: 0; }
            
            .cta-section { margin: 0 16px 40px; padding: 40px 20px; flex-direction: column; text-align: center; gap: 24px; border-radius: 20px; }
            .cta-text h2 { font-size: 26px; line-height: 1.3; }
            .cta-btns { width: 100%; justify-content: center; flex-direction: column; gap: 12px; }
            .cta-btns .btn-lg { width: 100%; justify-content: center; }
            
            footer { padding: 24px 16px; flex-direction: column; text-align: center; gap: 12px; }
        }

        @media (max-width: 380px) {
            .nav-actions .btn { padding: 6px 10px; font-size: 12px; }
            .logo-name { font-size: 17px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-inner">
        <a href="/" class="logo">
            <div class="logo-mark">C</div>
            <span class="logo-name">CloudVPS</span>
        </a>
        <div class="nav-menu">
            <a href="#features">Tính năng</a>
            <a href="#plans">Bảng giá</a>
            <a href="#how">Quy trình</a>
        </div>
        <div class="nav-actions">
            @auth
                <a href="{{ route('vps.dashboard') }}" class="btn btn-outline">Dashboard</a>
                <a href="{{ route('vps.create') }}" class="btn btn-blue">Tạo VPS</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn btn-blue">Đăng ký</a>
            @endauth
        </div>
    </div>
</nav>

<section>
    <div class="hero">
        <div class="hero-left">
            <div class="hero-badge">
                <span class="badge-dot">☁</span>
                Hạ tầng Google Cloud
            </div>
            <h1>Thuê VPS Google Cloud<br><span class="hl">nhanh, gọn, dễ dùng</span><br>cho người Việt.</h1>
            <p class="hero-desc">Chọn gói, nạp tiền bằng VietQR, tạo VPS và nhận thông tin truy cập trong cùng một bảng điều khiển. Phù hợp chạy website, bot, API và dịch vụ online.</p>
            <div class="hero-btns">
                @auth
                    <a href="{{ route('vps.dashboard') }}" class="btn btn-blue btn-lg">Vào Dashboard</a>
                    <a href="{{ route('vps.create') }}" class="btn btn-outline btn-lg">Tạo VPS mới</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-blue btn-lg">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="btn btn-outline btn-lg">Đăng ký</a>
                @endauth
            </div>
            <div class="hero-trust">
                <div class="trust-avatars">
                    <span>T</span><span>N</span><span>H</span><span>A</span>
                </div>
                <div class="trust-text"><strong>Tự động hóa</strong> từ nạp tiền đến tạo VPS</div>
            </div>
        </div>

        <div class="hero-right">
            <div style="position:relative;">
                <div class="hero-card">
                    <div class="card-topbar">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green2"></span>
                        <div class="card-url">cloadvps.ct.ws/dashboard</div>
                    </div>
                    <div class="card-body">
                        <div class="dash-header">
                            <div class="dash-title">VPS của tôi</div>
                            <div class="dash-new">+ Tạo VPS</div>
                        </div>
                        <div class="vps-item">
                            <div class="vps-icon vi-blue">🖥</div>
                            <div class="vps-info">
                                <div class="vps-name">web-production-01</div>
                                <div class="vps-ip">34.126.88.211</div>
                            </div>
                            <div class="vps-status">
                                <span class="status-dot on"></span>
                                <span class="status-on">Đang chạy</span>
                            </div>
                        </div>
                        <div class="vps-item">
                            <div class="vps-icon vi-teal">🖥</div>
                            <div class="vps-info">
                                <div class="vps-name">api-server-sg</div>
                                <div class="vps-ip">35.198.245.103</div>
                            </div>
                            <div class="vps-status">
                                <span class="status-dot on"></span>
                                <span class="status-on">Đang chạy</span>
                            </div>
                        </div>
                        <div class="vps-item">
                            <div class="vps-icon vi-violet">🖥</div>
                            <div class="vps-info">
                                <div class="vps-name">dev-staging</div>
                                <div class="vps-ip">104.197.12.88</div>
                            </div>
                            <div class="vps-status">
                                <span class="status-dot off"></span>
                                <span class="status-off">Đã tắt</span>
                            </div>
                        </div>
                        <div class="dash-stats">
                            <div class="mini-stat">
                                <div class="mini-val blue">3</div>
                                <div class="mini-lbl">VPS</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-val green">2</div>
                                <div class="mini-lbl">Đang chạy</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-val">245k</div>
                                <div class="mini-lbl">Số dư</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="float-badge top-right">
                    <div class="fb-icon fb-green">✅</div>
                    <div>
                        <div style="font-size:13px;color:#0f1923;">Máy đã sẵn sàng</div>
                        <div style="font-size:11px;color:#8b95a2;font-weight:500;">Tạo máy chỉ vài phút</div>
                    </div>
                </div>
                <div class="float-badge bot-left">
                    <div class="fb-icon fb-blue">💳</div>
                    <div>
                        <div style="font-size:13px;color:#0f1923;">VietQR nhận tiền</div>
                        <div style="font-size:11px;color:#8b95a2;font-weight:500;">+100.000đ • vừa xong</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="stats-bar">
    <div class="stats-bar-inner">
        <div class="sbar-item">
            <div class="sbar-icon">⚡</div>
            <div>
                <div class="sbar-val">Nhanh</div>
                <div class="sbar-lbl">Cấp máy tự động</div>
            </div>
        </div>
        <div class="sbar-item">
            <div class="sbar-icon">💳</div>
            <div>
                <div class="sbar-val">VietQR</div>
                <div class="sbar-lbl">Nạp tiền nội địa</div>
            </div>
        </div>
        <div class="sbar-item">
            <div class="sbar-icon">📡</div>
            <div>
                <div class="sbar-val">GCP</div>
                <div class="sbar-lbl">Hạ tầng Google Cloud</div>
            </div>
        </div>
        <div class="sbar-item">
            <div class="sbar-icon">🔄</div>
            <div>
                <div class="sbar-val">Auto</div>
                <div class="sbar-lbl">Đồng bộ IP & trạng thái</div>
            </div>
        </div>
    </div>
</div>

<section id="features" class="features-section">
    <div class="section-eyebrow">Tính năng</div>
    <h2 class="section-title">Tạo và quản lý VPS<br>không cần thao tác rườm rà.</h2>
    <p class="section-sub">CloudVPS gom các việc quan trọng khi thuê máy chủ vào một nơi: nạp tiền, tạo máy, xem IP, đổi mật khẩu, mở port, reboot và gia hạn.</p>
    <div class="feat-grid">
        <div class="feat-card reveal">
            <div class="feat-icon fi-blue">⚡</div>
            <h3>Cấp máy tự động</h3>
            <p>Chọn cấu hình, hệ điều hành và khu vực triển khai. Hệ thống gửi lệnh tạo máy trực tiếp tới Google Cloud.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon fi-teal">💳</div>
            <h3>Thanh toán VietQR</h3>
            <p>Nạp tiền bằng VietQR, theo dõi số dư và lịch sử giao dịch. Mã nạp riêng giúp đối soát chính xác từng đơn.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon fi-violet">🔧</div>
            <h3>Quản trị nhanh</h3>
            <p>Xem IP, mật khẩu, ngày hết hạn, trạng thái máy và thao tác reboot hoặc gia hạn khi cần.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon fi-amber">📍</div>
            <h3>Khu vực gần Việt Nam</h3>
            <p>Triển khai tại Singapore, Đài Loan, Tokyo — giảm độ trễ tối đa cho người dùng trong nước.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon fi-green">🔒</div>
            <h3>Bảo mật mặc định</h3>
            <p>Mật khẩu mã hóa, SSH key tùy chọn và firewall cơ bản được cấu hình sẵn khi tạo máy.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon fi-red">📊</div>
            <h3>Theo dõi trạng thái</h3>
            <p>Dashboard hiển thị trạng thái máy, IP, thời gian còn lại và các thao tác quản trị thường dùng.</p>
        </div>
    </div>
</section>

<section id="plans" class="pricing-section">
    <div class="pricing-inner">
        <div class="section-eyebrow">Bảng giá</div>
        <h2 class="section-title">Cấu hình dễ chọn,<br>chi phí dễ kiểm soát.</h2>
        <p class="section-sub">Chọn gói theo nhu cầu thực tế: chạy web nhỏ, bot, API, tool nội bộ hoặc dịch vụ cần nhiều RAM hơn.</p>
        <div class="plans-row">
            <div class="plan reveal">
                <div class="plan-tier">Starter</div>
                <div class="plan-name">G1 Basic</div>
                <div class="plan-tagline">Website cá nhân, học tập, bot nhẹ</div>
                <div class="plan-price-block">
                    <span class="plan-price">45k</span>
                    <span class="plan-period">/ tháng</span>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-specs">
                    <div class="plan-spec"><span class="spec-k">CPU</span><span class="spec-v">2 vCPU</span></div>
                    <div class="plan-spec"><span class="spec-k">RAM</span><span class="spec-v">8 GB</span></div>
                    <div class="plan-spec"><span class="spec-k">Disk</span><span class="spec-v">50 GB SSD</span></div>
                    <div class="plan-spec"><span class="spec-k">Băng thông</span><span class="spec-v">Linh hoạt</span></div>
                </div>
                <a href="{{ Auth::check() ? route('vps.create') : route('register') }}" class="plan-cta">Chọn gói này →</a>
            </div>
            <div class="plan highlight reveal" style="position:relative;">
                <div class="plan-badge">Phổ biến nhất</div>
                <div class="plan-tier">Professional</div>
                <div class="plan-name">G2 Pro</div>
                <div class="plan-tagline">Web bán hàng, dịch vụ nền, API</div>
                <div class="plan-price-block">
                    <span class="plan-price">89k</span>
                    <span class="plan-period">/ tháng</span>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-specs">
                    <div class="plan-spec"><span class="spec-k">CPU</span><span class="spec-v">2 vCPU</span></div>
                    <div class="plan-spec"><span class="spec-k">RAM</span><span class="spec-v">12 GB</span></div>
                    <div class="plan-spec"><span class="spec-k">Disk</span><span class="spec-v">70 GB SSD</span></div>
                    <div class="plan-spec"><span class="spec-k">Băng thông</span><span class="spec-v">Linh hoạt</span></div>
                </div>
                <a href="{{ Auth::check() ? route('vps.create') : route('register') }}" class="plan-cta">Chọn gói này →</a>
            </div>
            <div class="plan reveal">
                <div class="plan-tier">Enterprise</div>
                <div class="plan-name">G4 Titan</div>
                <div class="plan-tagline">Dịch vụ nhiều người dùng, xử lý nặng</div>
                <div class="plan-price-block">
                    <span class="plan-price">279k</span>
                    <span class="plan-period">/ tháng</span>
                </div>
                <div class="plan-divider"></div>
                <div class="plan-specs">
                    <div class="plan-spec"><span class="spec-k">CPU</span><span class="spec-v">4 vCPU</span></div>
                    <div class="plan-spec"><span class="spec-k">RAM</span><span class="spec-v">16 GB</span></div>
                    <div class="plan-spec"><span class="spec-k">Disk</span><span class="spec-v">80 GB SSD</span></div>
                    <div class="plan-spec"><span class="spec-k">Băng thông</span><span class="spec-v">Linh hoạt</span></div>
                </div>
                <a href="{{ Auth::check() ? route('vps.create') : route('register') }}" class="plan-cta">Chọn gói này →</a>
            </div>
        </div>
    </div>
</section>

<section id="how" class="steps-section">
    <div class="section-eyebrow">Quy trình</div>
    <h2 class="section-title">Ba bước để có<br>máy chủ mới.</h2>
    <p class="section-sub" style="margin-bottom:64px;">Từ lúc đăng ký đến khi nhận IP đều nằm trong dashboard, không cần nhắn tin thủ công để chờ cấp máy.</p>
    <div class="steps-row">
        <div class="step reveal">
            <div class="step-circle">1</div>
            <h3>Nạp số dư</h3>
            <p>Tạo lệnh nạp VietQR với mã riêng. Khi giao dịch được xác nhận, số dư sẽ được cộng vào tài khoản.</p>
        </div>
        <div class="step reveal">
            <div class="step-circle">2</div>
            <h3>Chọn cấu hình</h3>
            <p>Chọn gói, hệ điều hành, khu vực gần Việt Nam và thời hạn thuê. Hệ thống tự gửi lệnh lên Google Cloud.</p>
        </div>
        <div class="step reveal">
            <div class="step-circle">3</div>
            <h3>Quản lý VPS</h3>
            <p>Theo dõi IP, trạng thái, mật khẩu và ngày hết hạn. Reboot, mở port hoặc gia hạn ngay trên web.</p>
        </div>
    </div>
</section>

<div class="cta-section">
    <div class="cta-text">
        <h2>Sẵn sàng có VPS<br>cho dự án của bạn?</h2>
        <p>Đăng ký tài khoản, nạp số dư và tạo máy chủ Google Cloud ngay trong CloudVPS.</p>
    </div>
    <div class="cta-btns">
        @auth
            <a href="{{ route('vps.dashboard') }}" class="btn btn-white btn-lg">Vào Dashboard</a>
            <a href="{{ route('vps.create') }}" class="btn btn-outline-white btn-lg">Tạo VPS mới</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-white btn-lg">Đăng nhập</a>
            <a href="{{ route('register') }}" class="btn btn-outline-white btn-lg">Đăng ký</a>
        @endauth
    </div>
</div>

<footer>
    <div class="logo">
        <div class="logo-mark">C</div>
        <span class="logo-name" style="font-size:16px;">CloudVPS</span>
    </div>
    <div class="footer-copy">© {{ date('Y') }} CloudVPS - VPS Google Cloud tự động cho người dùng Việt Nam</div>
</footer>

<x-support.zalo-button />

<script>
    const obs = new IntersectionObserver(entries => {
        entries.forEach((e, i) => {
            if (e.isIntersecting) {
                setTimeout(() => e.target.classList.add('visible'), 80);
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.08 });

    document.querySelectorAll('.reveal').forEach((el, i) => {
        el.style.transitionDelay = (i % 3) * 0.1 + 's';
        obs.observe(el);
    });
</script>
</body>
</html>
