@extends('layouts.app')

@section('title', $vps->name . ' - Chi tiết VPS CloudVPS')
@section('meta_description', 'Xem thông tin VPS, IP, mật khẩu, cấu hình máy chủ và thao tác quản trị trên CloudVPS.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $isRunning   = in_array($vps->status, ['Đang chạy', 'RUNNING']);
    $statusClass = $vps->statusBadgeClass();
    $loginUsers = [
        'windows' => 'admin',
        'ubuntu' => 'ubuntu',
        'debian' => 'debian',
        'rocky' => 'rocky',
        'almalinux' => 'almalinux',
        'centos' => 'cloud-user',
        'centos-stream' => 'cloud-user',
    ];
    $loginUser = $loginUsers[$vps->os] ?? 'cloud-user';
    $specs = [
        ['label' => 'Gói cấu hình', 'value' => strtoupper($vps->machine_type)],
        ['label' => 'Vi xử lý',     'value' => ($vps->cpu ?? 2) . ' vCPU'],
        ['label' => 'Bộ nhớ',       'value' => ($vps->ram ?? 4) . ' GB'],
        ['label' => 'Lưu trữ',      'value' => ($vps->disk ?? 20) . ' GB NVMe'],
        ['label' => 'Băng thông',   'value' => 'Unlimited Premium'],
        ['label' => 'Khu vực',      'value' => $vps->zone],
        ['label' => 'Ngày tạo',     'value' => $vps->created_at->format('d/m/Y H:i')],
    ];
@endphp

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h3 fw-bold mb-0">{{ $vps->name }}</h1>
            <span class="status-pill {{ $statusClass }}">{{ $vps->status }}</span>
        </div>
        <p class="text-secondary mb-0">{{ $vps->zone }} · {{ strtoupper($vps->os ?? 'linux') }}</p>
    </div>
    <a href="{{ route('vps.dashboard') }}" class="btn btn-outline-secondary fw-semibold show-back-btn">← Quay lại</a>
</div>

<div class="vps-note mb-4">
    <span>i</span>
    <div>
        <div class="fw-bold mb-1">Lưu ý khi mới tạo VPS</div>
        Máy chủ mới cần 3 - 5 phút để cấp IP, cài hệ điều hành và mở kết nối. Vui lòng đợi rồi tải lại trang chi tiết VPS.
    </div>
</div>

<div class="vps-ip-card mb-4">
    <div class="vps-ip-content">
        <div class="vps-ip-label">Địa chỉ IPv4</div>
        <div class="vps-ip-value">{{ $vps->public_ip ?? 'Đang khởi tạo...' }}</div>
        <div class="d-flex flex-wrap gap-2">
            <span class="hero-chip">Port: {{ $vps->os === 'windows' ? '3389 (RDP)' : '22 (SSH)' }}</span>
            <span class="hero-chip">{{ $vps->cpu }}C · {{ $vps->ram }}GB · {{ $vps->disk }}GB</span>
            @if($vps->expires_at)
                <span class="hero-chip">Hết hạn: {{ $vps->expires_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
            @endif
        </div>
    </div>
    <div class="vps-ip-orb"></div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="show-panel h-100">
            <div class="show-panel-head">
                <div>
                    <h2>Thông tin đăng nhập</h2>
                    <p>Tài khoản truy cập máy chủ.</p>
                </div>
                <span class="panel-icon">🔐</span>
            </div>

            <div class="credential-box">
                <div class="credential-row">
                    <span>Username</span>
                    <strong>{{ $loginUser }}</strong>
                </div>
                <div class="credential-row password-row">
                    <div>
                        <span>Mật khẩu</span>
                        <strong id="pwDisplay" data-hidden="1" class="password-display">************</strong>
                    </div>
                    <div class="btn-group">
                        <button onclick="togglePw()" type="button" class="btn btn-outline-secondary fw-semibold" id="togglePwBtn">Mở</button>
                        <button onclick="copyPw()" type="button" class="btn btn-primary fw-semibold" id="copyBtn">Copy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="show-panel h-100">
            <div class="show-panel-head">
                <div>
                    <h2>Thông số máy chủ</h2>
                    <p>Cấu hình và khu vực triển khai.</p>
                </div>
                <span class="panel-icon alt">📊</span>
            </div>
            <div class="spec-grid">
                @foreach($specs as $spec)
                    <div class="spec-item">
                        <span>{{ $spec['label'] }}</span>
                        <strong>{{ $spec['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="show-panel mt-4">
    <div class="show-panel-head">
        <div>
            <h2>Hành động</h2>
            <p>Khởi động lại, đổi mật khẩu hoặc gia hạn VPS.</p>
        </div>
        <span class="panel-icon warn">⚡</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <form action="{{ route('vps.reboot', $vps->id) }}" method="POST" onsubmit="return confirm('Khởi động lại sẽ làm gián đoạn tiến trình đang chạy. Tiếp tục?');" class="action-card h-100">
                @csrf
                <div class="action-icon success">↻</div>
                <div class="action-title">Khởi động lại</div>
                <div class="action-sub">Reboot máy chủ hiện tại.</div>
                <button type="submit" class="btn btn-outline-success w-100 fw-semibold mt-auto">Reboot VPS</button>
            </form>
        </div>

        <div class="col-lg-4">
            <form action="{{ route('vps.password', $vps->id) }}" method="POST" onsubmit="return confirm('Mật khẩu sẽ thay đổi và máy chủ sẽ khởi động lại. Tiếp tục?');" class="action-card h-100">
                @csrf
                <div class="action-icon primary">🔑</div>
                <label for="new_password" class="action-title">Đổi mật khẩu</label>
                <div class="action-sub">Máy chủ sẽ reboot sau khi đổi.</div>
                <div class="input-group mt-auto">
                    <input id="new_password" type="text" name="new_password" class="form-control" placeholder="Mật khẩu mới" required minlength="8">
                    <button type="submit" class="btn btn-primary fw-semibold">Lưu</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <form action="{{ route('vps.renew', $vps->id) }}" method="POST" onsubmit="return confirmRenew();" class="action-card h-100">
                @csrf
                <div class="action-icon purple">📅</div>
                <label for="renewDays" class="action-title">Gia hạn gói cước</label>
                <div class="action-sub">Chọn thời hạn và thanh toán.</div>
                <div class="input-group mt-auto">
                    <select name="days" id="renewDays" data-base-price="{{ $renewBasePrice ?? 0 }}" onchange="updateRenewPrice()" class="form-select">
                        @foreach($renewOptions as $days => $price)
                            <option value="{{ $days }}" {{ $days === 30 ? 'selected' : '' }}>
                                {{ [1 => '1 ngày', 7 => '7 ngày', 30 => '1 tháng', 90 => '3 tháng', 180 => '6 tháng', 365 => '1 năm'][$days] }}
                                - {{ number_format($price) }} VND
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary fw-semibold">Mua</button>
                </div>
                <div class="form-text">Phí gia hạn: <strong id="renewPriceText" class="text-primary">0 VND</strong></div>
            </form>
        </div>
    </div>
</div>


@if(Auth::user()->is_admin)
<div class="show-panel mt-4" id="firewall-panel">
    <div class="show-panel-head">
        <div>
            <h2>Firewall / Mo port</h2>
            <p>Public chi cho phep 80, 443, 8080, 8443. Port quan tri/dev phai gioi han IP nguon.</p>
        </div>
        <span class="panel-icon firewall">FW</span>
    </div>

    <form action="{{ route('vps.firewall.open', $vps->id) }}" method="POST" class="firewall-form mb-4">
        @csrf
        <div class="firewall-form-grid">
            <div>
                <label for="firewallProtocol" class="form-label fw-bold">Protocol</label>
                <select name="protocol" id="firewallProtocol" class="form-select" required>
                    <option value="tcp" {{ old('protocol', 'tcp') === 'tcp' ? 'selected' : '' }}>TCP</option>
                    <option value="udp" {{ old('protocol') === 'udp' ? 'selected' : '' }}>UDP</option>
                </select>
            </div>
            <div>
                <label for="firewallPort" class="form-label fw-bold">Port</label>
                <input
                    id="firewallPort"
                    type="text"
                    name="port"
                    value="{{ old('port') }}"
                    class="form-control"
                    placeholder="80 hoac 3000-3010"
                    inputmode="numeric"
                    required
                >
            </div>
            <div>
                <label for="firewallSourceType" class="form-label fw-bold">Nguon truy cap</label>
                <select name="source_type" id="firewallSourceType" class="form-select" onchange="toggleFirewallSource()" required>
                    <option value="any" {{ old('source_type', 'any') === 'any' ? 'selected' : '' }}>Tat ca IP</option>
                    <option value="my_ip" {{ old('source_type') === 'my_ip' ? 'selected' : '' }}>Chi IP hien tai cua toi</option>
                    <option value="custom" {{ old('source_type') === 'custom' ? 'selected' : '' }}>IP/CIDR tuy chinh</option>
                </select>
            </div>
            <div id="firewallSourceRangeWrap">
                <label for="firewallSourceRange" class="form-label fw-bold">IP/CIDR</label>
                <input
                    id="firewallSourceRange"
                    type="text"
                    name="source_range"
                    value="{{ old('source_range') }}"
                    class="form-control"
                    placeholder="203.0.113.10 hoac 203.0.113.0/24"
                >
            </div>
            <div class="firewall-submit">
                <button type="submit" class="btn btn-primary fw-bold w-100">Mo port</button>
            </div>
        </div>
        <div class="form-text mt-2">Tat ca IP chi dung cho 80, 443, 8080, 8443. SSH/RDP/dev/database phai chon IP hien tai hoac CIDR rieng.</div>
    </form>

    <div class="firewall-rules">
        @forelse($firewallRules as $rule)
            <div class="firewall-rule-row">
                <div>
                    <div class="firewall-rule-main">{{ strtoupper($rule->protocol) }} {{ $rule->portLabel() }}</div>
                    <div class="firewall-rule-sub">
                        Nguon: {{ $rule->source_range }} - Trang thai: {{ $rule->sync_status }}
                        @if($rule->sync_error)
                            <span class="d-block text-danger mt-1">{{ $rule->sync_error }}</span>
                        @endif
                    </div>
                </div>
                <form action="{{ route('vps.firewall.delete', [$vps->id, $rule->id]) }}" method="POST" class="m-0" onsubmit="return confirm('Dong port {{ strtoupper($rule->protocol) }} {{ $rule->portLabel() }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold">Dong</button>
                </form>
            </div>
        @empty
            <div class="firewall-empty">Chua co port custom nao duoc mo cho VPS nay.</div>
        @endforelse
    </div>
</div>

@endif

<div class="danger-panel mt-4 mb-5">
    <div>
        <h2>Vùng nguy hiểm</h2>
        <p>Hành động này sẽ xóa sạch dữ liệu, giải phóng IP và không thể khôi phục.</p>
    </div>
    <form action="{{ route('vps.destroy', $vps->id) }}" method="POST" onsubmit="return confirm('CẢNH BÁO: Xóa vĩnh viễn không thể khôi phục. Bạn chắc chắn chứ?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-lg fw-bold">🗑 Xóa máy chủ vĩnh viễn</button>
    </form>
</div>
@endsection

@push('styles')
<style>
    .show-back-btn {
        border-radius: 10px;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }
    .status-pill::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }
    .vps-note {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 14px;
        padding: 14px 16px;
        color: #9a3412;
        box-shadow: 0 .75rem 1.5rem rgba(249, 115, 22, .06);
    }
    .vps-note > span {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ffedd5;
        color: #ea580c;
        font-weight: 900;
        flex-shrink: 0;
    }
    .vps-ip-card {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 58%, #06b6d4 100%);
        border-radius: 18px;
        color: #fff;
        overflow: hidden;
        position: relative;
        box-shadow: 0 1rem 2rem rgba(37, 99, 235, .18);
        min-height: 180px;
    }
    .vps-ip-content {
        position: relative;
        z-index: 1;
        padding: 28px;
    }
    .vps-ip-label {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: .72;
        margin-bottom: 8px;
    }
    .vps-ip-value {
        font-family: 'Roboto Mono', monospace;
        font-size: clamp(28px, 4vw, 44px);
        font-weight: 800;
        letter-spacing: .5px;
        margin-bottom: 18px;
        overflow-wrap: anywhere;
    }
    .hero-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .18);
        color: #fff;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        backdrop-filter: blur(8px);
    }
    .vps-ip-orb {
        position: absolute;
        width: 150px;
        height: 150px;
        right: -34px;
        top: -42px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .13);
    }
    .show-panel {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .06);
    }
    .show-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }
    .show-panel-head h2 {
        font-size: 18px;
        font-weight: 800;
        margin: 0 0 4px;
    }
    .show-panel-head p {
        color: #64748b;
        font-size: 13px;
        margin: 0;
    }
    .panel-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 99, 235, .1);
        flex-shrink: 0;
    }
    .panel-icon.alt { background: rgba(6, 182, 212, .12); }
    .panel-icon.warn { background: rgba(245, 158, 11, .13); }
    .credential-box {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .credential-row,
    .spec-item {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 12px;
        padding: 14px 16px;
    }
    .credential-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }
    .credential-row span,
    .spec-item span {
        display: block;
        color: #64748b;
        font-size: 12px;
        margin-bottom: 4px;
    }
    .credential-row strong,
    .spec-item strong {
        color: #0f172a;
        font-family: 'Roboto Mono', monospace;
        font-weight: 800;
    }
    .password-row {
        align-items: flex-end;
    }
    .spec-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .action-card {
        min-height: 210px;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        padding: 18px;
        text-decoration: none;
    }
    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        font-weight: 900;
        font-size: 18px;
    }
    .action-icon.success { background: rgba(16, 185, 129, .12); color: #059669; }
    .action-icon.primary { background: rgba(37, 99, 235, .1);  color: #2563eb; }
    .action-icon.purple  { background: rgba(139, 92, 246, .12); color: #7c3aed; }
    .action-title {
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
        margin-bottom: 4px;
    }
    .action-sub {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .panel-icon.firewall { background: rgba(14, 165, 233, .12); color: #0284c7; font-size: 12px; font-weight: 900; }
    .firewall-form {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        padding: 16px;
    }
    .firewall-form-grid {
        display: grid;
        grid-template-columns: 120px minmax(150px, 1fr) minmax(190px, 1fr) minmax(220px, 1fr) 120px;
        gap: 12px;
        align-items: end;
    }
    .firewall-rule-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        background: #fff;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 10px;
    }
    .firewall-rule-main {
        color: #0f172a;
        font-family: 'Roboto Mono', monospace;
        font-size: 14px;
        font-weight: 900;
    }
    .firewall-rule-sub {
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
        overflow-wrap: anywhere;
    }
    .firewall-empty {
        border: 1px dashed rgba(148, 163, 184, .45);
        border-radius: 12px;
        padding: 18px;
        color: #64748b;
        text-align: center;
        background: #fff;
    }
    .danger-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        background: #fff5f5;
        border: 1px solid #fecaca;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 .75rem 1.5rem rgba(239, 68, 68, .08);
    }
    .danger-panel h2 {
        color: #dc2626;
        font-size: 18px;
        font-weight: 800;
        margin: 0 0 5px;
    }
    .danger-panel p {
        color: #7f1d1d;
        margin: 0;
    }
    .danger-panel form {
        flex-shrink: 0;
    }
    .password-display {
        letter-spacing: 2px;
    }
    @media (max-width: 768px) {
        .firewall-form-grid {
            grid-template-columns: 1fr;
        }
        .firewall-rule-row {
            align-items: stretch;
            flex-direction: column;
        }
        .firewall-rule-row .btn {
            width: 100%;
        }
        .credential-row,
        .danger-panel {
            align-items: stretch;
            flex-direction: column;
        }
        .spec-grid {
            grid-template-columns: 1fr;
        }
        .danger-panel form,
        .danger-panel .btn {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>

function toggleFirewallSource() {
    const select = document.getElementById('firewallSourceType');
    const wrap = document.getElementById('firewallSourceRangeWrap');
    const input = document.getElementById('firewallSourceRange');
    if (!select || !wrap || !input) return;

    const isCustom = select.value === 'custom';
    wrap.style.display = isCustom ? '' : 'none';
    input.required = isCustom;
    if (!isCustom) input.value = '';
}

// Pre-computed option prices from controller (no client-side math needed)
const renewOptionPrices = @json($renewOptions);

function getSelectedRenewPrice() {
    const select = document.getElementById('renewDays');
    if (!select) return { days: 0, total: 0 };
    const days = parseInt(select.value || '0', 10);
    return { days, total: renewOptionPrices[days] ?? 0 };
}

function updateRenewPrice() {
    const label = document.getElementById('renewPriceText');
    if (!label) return;
    const price = getSelectedRenewPrice();
    label.textContent = price.total.toLocaleString('vi-VN') + ' VND';
}

function confirmRenew() {
    const price = getSelectedRenewPrice();
    if (price.total <= 0) {
        return confirm('Chưa xác định được giá gia hạn. Bạn vẫn muốn tiếp tục?');
    }
    return confirm('Gia hạn ' + price.days + ' ngày với phí ' + price.total.toLocaleString('vi-VN') + ' VND. Xác nhận thanh toán?');
}

// ─── Password reveal via authenticated AJAX endpoint ─────────────────────────
let _cachedPassword = null;

async function fetchPassword() {
    if (_cachedPassword !== null) return _cachedPassword;
    try {
        const res = await fetch('{{ route('vps.credentials', $vps->id) }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        _cachedPassword = data.password ?? '';
    } catch (e) {
        _cachedPassword = '';
    }
    return _cachedPassword;
}

async function togglePw() {
    const el  = document.getElementById('pwDisplay');
    const btn = document.getElementById('togglePwBtn');
    const isHidden = el.dataset.hidden === '1';

    if (isHidden) {
        const pw = await fetchPassword();
        el.textContent = pw || '********';
        el.dataset.hidden = '0';
        el.style.letterSpacing = '0';
        btn.textContent = 'Ẩn';
    } else {
        el.textContent = '************';
        el.dataset.hidden = '1';
        el.style.letterSpacing = '2px';
        btn.textContent = 'Mở';
    }
}

async function copyPw() {
    const pw = await fetchPassword();
    if (!pw) return;

    navigator.clipboard.writeText(pw).then(function () {
        const btn = document.getElementById('copyBtn');
        btn.textContent = 'Đã copy';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        setTimeout(function () {
            btn.textContent = 'Copy';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    updateRenewPrice();
    toggleFirewallSource();
});
</script>
@endpush
