<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CloudVPS - Thuê VPS Google Cloud tự động</title>
    <meta name="description" content="CloudVPS cung cấp VPS Google Cloud tự động, thanh toán VietQR, cấp máy nhanh và quản lý máy chủ dễ dàng cho người dùng Việt Nam.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="CloudVPS">
    <meta property="og:title" content="CloudVPS - Thuê VPS Google Cloud tự động">
    <meta property="og:description" content="Thuê VPS Google Cloud tự động, nạp tiền VietQR và quản lý máy chủ dễ dàng.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        :root {
            --ink: #17211f;
            --muted: #63716d;
            --line: #d9e2df;
            --surface: #ffffff;
            --soft: #f4f8f6;
            --green: #0f8a63;
            --green-dark: #0a6b4d;
            --coral: #df5b4f;
            --amber: #e3a32d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--surface);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
        }

        a {
            text-decoration: none;
        }

        .site-nav {
            position: fixed;
            inset: 0 0 auto;
            z-index: 20;
            border-bottom: 1px solid rgba(255, 255, 255, .18);
            background: rgba(23, 33, 31, .72);
            backdrop-filter: blur(14px);
        }

        .brand-mark {
            display: inline-flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #ffffff;
            color: var(--green-dark);
            font-weight: 900;
        }

        .brand-text,
        .nav-link-plain {
            color: #ffffff;
        }

        .nav-link-plain {
            font-size: 14px;
            font-weight: 700;
            opacity: .86;
        }

        .nav-link-plain:hover {
            color: #ffffff;
            opacity: 1;
        }

        .btn {
            border-radius: 8px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .btn-main {
            border-color: var(--green);
            background: var(--green);
            color: #ffffff;
        }

        .btn-main:hover {
            border-color: var(--green-dark);
            background: var(--green-dark);
            color: #ffffff;
        }

        .btn-ghost-light {
            border-color: rgba(255, 255, 255, .55);
            color: #ffffff;
            background: rgba(255, 255, 255, .08);
        }

        .btn-ghost-light:hover {
            border-color: #ffffff;
            color: #ffffff;
            background: rgba(255, 255, 255, .16);
        }

        .hero {
            min-height: 88vh;
            display: flex;
            align-items: center;
            padding: 112px 0 58px;
            color: #ffffff;
            background:
                linear-gradient(90deg, rgba(10, 31, 28, .90) 0%, rgba(10, 31, 28, .78) 42%, rgba(10, 31, 28, .38) 100%),
                url("https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1800&q=82") center / cover no-repeat;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, .32);
            border-radius: 999px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, .1);
            font-size: 13px;
            font-weight: 800;
        }

        .hero h1 {
            max-width: 760px;
            margin: 18px 0 18px;
            font-size: clamp(42px, 6vw, 76px);
            line-height: .98;
            font-weight: 900;
        }

        .hero .lead {
            max-width: 650px;
            color: rgba(255, 255, 255, .84);
            font-size: 19px;
            line-height: 1.65;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1px;
            max-width: 680px;
            margin-top: 34px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            background: rgba(255, 255, 255, .16);
        }

        .hero-metric {
            min-height: 94px;
            padding: 18px;
            background: rgba(5, 24, 21, .42);
        }

        .hero-metric strong {
            display: block;
            font-size: 24px;
            line-height: 1.1;
        }

        .hero-metric span {
            display: block;
            margin-top: 8px;
            color: rgba(255, 255, 255, .74);
            font-size: 13px;
            font-weight: 700;
        }

        .section {
            padding: 78px 0;
        }

        .section-soft {
            background: var(--soft);
        }

        .section-title {
            max-width: 720px;
            margin-bottom: 34px;
        }

        .section-title .eyebrow {
            margin-bottom: 10px;
            color: var(--green);
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .section-title h2 {
            margin: 0;
            font-size: clamp(30px, 4vw, 48px);
            line-height: 1.08;
            font-weight: 900;
        }

        .section-title p {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.6;
        }

        .feature-card,
        .price-card,
        .flow-step {
            height: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #ffffff;
        }

        .feature-card {
            padding: 26px;
        }

        .feature-icon {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #ffffff;
            font-weight: 900;
        }

        .feature-icon.green {
            background: var(--green);
        }

        .feature-icon.coral {
            background: var(--coral);
        }

        .feature-icon.amber {
            background: var(--amber);
        }

        .feature-card h3,
        .price-card h3,
        .flow-step h3 {
            margin: 18px 0 10px;
            font-size: 20px;
            font-weight: 900;
        }

        .feature-card p,
        .flow-step p {
            color: var(--muted);
            line-height: 1.6;
        }

        .price-card {
            padding: 26px;
        }

        .price-card.featured {
            border-color: rgba(15, 138, 99, .44);
            box-shadow: 0 18px 48px rgba(15, 138, 99, .14);
        }

        .price-value {
            margin: 12px 0 18px;
            font-size: 34px;
            font-weight: 900;
        }

        .price-value span {
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .spec-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .spec-list li {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-top: 1px solid var(--line);
            padding-top: 10px;
        }

        .spec-list strong {
            color: var(--ink);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .ops-band {
            color: #ffffff;
            background:
                linear-gradient(90deg, rgba(16, 48, 42, .94), rgba(16, 48, 42, .76)),
                url("https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1600&q=82") center / cover no-repeat;
        }

        .flow-step {
            padding: 22px;
            color: var(--ink);
        }

        .flow-number {
            display: inline-flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--green);
            color: #ffffff;
            font-weight: 900;
        }

        .cta {
            padding: 58px 0;
            background: #17211f;
            color: #ffffff;
        }

        .cta p {
            color: rgba(255, 255, 255, .72);
        }

        .site-footer {
            border-top: 1px solid var(--line);
            padding: 28px 0;
            color: var(--muted);
            background: #ffffff;
            font-size: 14px;
            font-weight: 700;
        }

        @media (max-width: 767.98px) {
            .site-nav .container {
                gap: 10px;
                flex-wrap: wrap;
            }

            .site-nav .nav-links {
                display: none;
            }

            .site-nav .d-flex.gap-2 {
                width: 100%;
            }

            .site-nav .d-flex.gap-2 .btn {
                flex: 1 1 0;
                min-width: 0;
                padding-left: 10px;
                padding-right: 10px;
                font-size: 14px;
            }

            .hero {
                min-height: 86vh;
                padding-top: 132px;
                padding-bottom: 44px;
            }

            .hero h1 {
                font-size: 34px;
                line-height: 1.08;
            }

            .hero .lead {
                font-size: 16px;
                line-height: 1.55;
            }

            .hero-kicker {
                flex-wrap: wrap;
                row-gap: 4px;
                font-size: 12px;
            }

            .hero-metrics {
                grid-template-columns: 1fr;
            }

            .hero-metric {
                min-height: 80px;
                padding: 14px;
            }

            .hero-metric strong {
                font-size: 20px;
            }

            .hero-metric span {
                font-size: 12px;
            }

            .section-title h2 {
                font-size: 30px;
            }

            .section-title p {
                font-size: 16px;
            }

            .price-value {
                font-size: 30px;
            }

            .section {
                padding: 56px 0;
            }

            .cta .container {
                text-align: left;
            }

            .cta .d-flex.gap-2 .btn {
                width: 100%;
            }
        }

        @media (max-width: 399.98px) {
            .site-nav .d-flex.gap-2 {
                flex-direction: column;
            }

            .site-nav .d-flex.gap-2 .btn {
                width: 100%;
            }

            .hero {
                padding-top: 146px;
            }

            .hero h1 {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <nav class="site-nav">
        <div class="container d-flex align-items-center justify-content-between py-3">
            <a class="d-inline-flex align-items-center gap-2" href="{{ url('/') }}" aria-label="CloudVPS">
                <span class="brand-mark">C</span>
                <span class="brand-text fw-black fw-bold">CloudVPS</span>
            </a>

            <div class="nav-links d-flex align-items-center gap-4">
                <a href="#plans" class="nav-link-plain">Gói VPS</a>
                <a href="#reliability" class="nav-link-plain">Tính năng</a>
            </div>

            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('vps.dashboard') }}" class="btn btn-main px-3">Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-main px-3">Bắt đầu</a>
                @endauth
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-kicker">
                    <span>Google Cloud VPS</span>
                    <span>Triển khai tự động</span>
                </div>

                <h1>Máy chủ sẵn sàng cho website, tool và dịch vụ online.</h1>
                <p class="lead mb-0">Tạo VPS Google Cloud, nhận IP, quản lý mật khẩu, gia hạn và theo dõi trạng thái trong một bảng điều khiển dành cho người dùng Việt Nam.</p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    @auth
                        <a href="{{ route('vps.create') }}" class="btn btn-main btn-lg px-4">Tạo VPS mới</a>
                        <a href="{{ route('vps.dashboard') }}" class="btn btn-ghost-light btn-lg px-4">Vào dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-main btn-lg px-4">Bắt đầu ngay</a>
                        <a href="#plans" class="btn btn-ghost-light btn-lg px-4">Xem gói</a>
                    @endauth
                </div>

                <div class="hero-metrics">
                    <div class="hero-metric">
                        <strong>VietQR</strong>
                        <span>Nạp tiền theo mã giao dịch</span>
                    </div>
                    <div class="hero-metric">
                        <strong>Triển khai</strong>
                        <span>Tài nguyên phù hợp nhu cầu</span>
                    </div>
                    <div class="hero-metric">
                        <strong>Auto sync</strong>
                        <span>Cập nhật IP và trạng thái</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="reliability" class="section section-soft">
            <div class="container">
                <div class="section-title">
                    <div class="eyebrow">Vận hành gọn</div>
                    <h2>Những thao tác quan trọng nằm trong một nơi.</h2>
                    <p>CloudVPS tập trung các việc thường gặp khi thuê máy chủ: tạo máy, thanh toán, xem thông tin truy cập, gia hạn, reboot và theo dõi trạng thái.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <span class="feature-icon green">01</span>
                            <h3>Cấp máy tự động</h3>
                            <p class="mb-0">Chọn cấu hình, hệ điều hành, khu vực triển khai và gửi lệnh tạo máy trực tiếp tới Google Cloud.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <span class="feature-icon coral">02</span>
                            <h3>Thanh toán rõ ràng</h3>
                            <p class="mb-0">Nạp tiền bằng VietQR, theo dõi số dư, lịch sử giao dịch và chi phí từng chu kỳ thuê.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <span class="feature-icon amber">03</span>
                            <h3>Quản trị nhanh</h3>
                            <p class="mb-0">Xem IP, mật khẩu, ngày hết hạn, trạng thái máy và thao tác reboot hoặc gia hạn khi cần.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="plans" class="section">
            <div class="container">
                <div class="section-title">
                    <div class="eyebrow">Gói phổ biến</div>
                    <h2>Cấu hình dễ chọn, chi phí dễ kiểm soát.</h2>
                    <p>Bắt đầu với gói nhẹ cho website nhỏ hoặc chọn cấu hình cao hơn cho workload cần nhiều CPU và RAM.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="price-card">
                            <h3>Starter G1</h3>
                            <p class="text-secondary mb-0">Website cá nhân, học tập, bot nhẹ.</p>
                            <div class="price-value">45.000đ <span>/ tháng</span></div>
                            <ul class="spec-list">
                                <li><span>CPU</span><strong>2 vCPU</strong></li>
                                <li><span>RAM</span><strong>8 GB</strong></li>
                                <li><span>Disk</span><strong>50 GB</strong></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="price-card featured">
                            <h3>Pro G2</h3>
                            <p class="text-secondary mb-0">Web bán hàng, dịch vụ nền.</p>
                            <div class="price-value">89.000đ <span>/ tháng</span></div>
                            <ul class="spec-list">
                                <li><span>CPU</span><strong>2 vCPU</strong></li>
                                <li><span>RAM</span><strong>12 GB</strong></li>
                                <li><span>Disk</span><strong>70 GB</strong></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="price-card">
                            <h3>Titan G4</h3>
                            <p class="text-secondary mb-0">Dịch vụ nhiều người dùng, xử lý nặng.</p>
                            <div class="price-value">279.000đ <span>/ tháng</span></div>
                            <ul class="spec-list">
                                <li><span>CPU</span><strong>4 vCPU</strong></li>
                                <li><span>RAM</span><strong>16 GB</strong></li>
                                <li><span>Disk</span><strong>80 GB</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    @auth
                        <a href="{{ route('vps.create') }}" class="btn btn-main btn-lg px-4">Xem toàn bộ gói VPS</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-main btn-lg px-4">Tạo tài khoản</a>
                    @endauth
                </div>
            </div>
        </section>

        <section id="process" class="section ops-band">
            <div class="container">
                <div class="section-title">
                    <div class="eyebrow text-white">Quy trình</div>
                    <h2>Ba bước để có máy chủ mới.</h2>
                    <p>Nạp tiền, chọn gói, nhận thông tin truy cập sau khi Google Cloud hoàn tất cấp phát tài nguyên.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="flow-step">
                            <span class="flow-number">1</span>
                            <h3>Nạp số dư</h3>
                            <p class="mb-0">Tạo lệnh nạp VietQR và theo dõi giao dịch trong tài khoản.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="flow-step">
                            <span class="flow-number">2</span>
                            <h3>Chọn cấu hình</h3>
                            <p class="mb-0">Chọn gói, hệ điều hành, khu vực gần Việt Nam và thời hạn thuê.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="flow-step">
                            <span class="flow-number">3</span>
                            <h3>Quản lý VPS</h3>
                            <p class="mb-0">Theo dõi IP, trạng thái, mật khẩu và ngày hết hạn ngay trong dashboard.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta">
            <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h2 class="h1 fw-black fw-bold mb-2">Sẵn sàng triển khai VPS mới?</h2>
                    <p class="mb-0">Tạo máy chủ Google Cloud và quản lý toàn bộ vòng đời thuê trong CloudVPS.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @auth
                        <a href="{{ route('vps.create') }}" class="btn btn-main btn-lg px-4">Tạo VPS</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-main btn-lg px-4">Đăng ký</a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>© {{ date('Y') }} CloudVPS</span>
            <span>VPS Google Cloud tự động cho người dùng Việt Nam</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
