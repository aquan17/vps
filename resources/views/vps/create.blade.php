@extends('layouts.app')

@section('title', 'Tạo VPS mới - CloudVPS')
@section('meta_description', 'Chọn cấu hình VPS, hệ điều hành, khu vực và thời hạn thuê trên CloudVPS.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $selectedPlan = old('plan', 'plan_mini');
    $selectedVoucher = old('voucher_code', '');
    /** is_admin = 1: gán VPS + Select2 AJAX (tối đa 20 kết quả mỗi lần gõ; tìm trên toàn DB). */
    $selectedOwnerId = $canAssignVps && isset($ownerUserPreselect)
        ? (int) $ownerUserPreselect->id
        : (int) Auth::id();
    $ownerBalances = $canAssignVps && $ownerUserPreselect
        ? [(string) $ownerUserPreselect->id => (int) ($ownerUserPreselect->balance ?? 0)]
        : [];
    $balance = $canAssignVps && $ownerUserPreselect
        ? (int) ($ownerUserPreselect->balance ?? 0)
        : (int) (Auth::user()->balance ?? 0);
    $durationOptions = [
        1 => '1 ngày',
        7 => '7 ngày',
        30 => '1 tháng',
        90 => '3 tháng',
        180 => '6 tháng',
        365 => '1 năm (-15%)',
    ];
    $selectedDuration = (int) old('duration', 30);
    if (!array_key_exists($selectedDuration, $durationOptions)) {
        $selectedDuration = array_key_first($durationOptions);
    }
    $planPayload = collect($plans)->map(function ($plan) {
        return [
            'name' => $plan['name'],
            'price' => (int) ($plan['price_per_month'] ?? $plan['price_per_day'] ?? 0),
            'cpu' => $plan['api_cores'] ?? $plan['cores'] ?? 0,
            'ram' => $plan['ram'] ?? $plan['api_ram'] ?? 0,
            'disk' => $plan['disk'] ?? 0,
        ];
    });
    $defaultZone = 'asia-southeast1-b';
    foreach ($uiZones as $zoneData) {
        if (str_contains($zoneData['id'], 'asia-southeast')) {
            $defaultZone = $zoneData['id'];
            break;
        }
    }
    $selectedZone = old('zone', $defaultZone);
@endphp

<div x-data="vpsCreatePage()" class="vps-create-page">
    <x-ui.page-header
        title="Khởi tạo máy chủ ảo"
        subtitle="Chọn tên máy chủ, cấu hình, hệ điều hành, khu vực và thanh toán trong một luồng rõ ràng."
    >
        <x-ui.button :href="route('vps.dashboard')" variant="secondary">
            Quay lại
        </x-ui.button>
    </x-ui.page-header>

    <form action="{{ route('vps.store') }}" method="POST" x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
        @csrf

        <div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 shadow-sm">
            <div class="flex items-start gap-3">
                {{-- <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-black uppercase text-white">Zalo</span> --}}
                <div>
                    <p class="mb-1 text-base font-extrabold text-slate-950">Muốn test VPS trước khi mua?</p>
                    <p class="mb-0 text-sm font-semibold text-slate-700">Inbox admin qua Zalo ở góc dưới phía bên phải để được hỗ trợ test nhanh.</p>
                </div>
            </div>
        </div>

        <div class="vps-create-layout grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-4">
                <x-ui.card padding="md">
                    <div class="mb-3 flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 font-mono text-sm font-bold text-white">1</span>
                        <div>
                            <h2 class="mb-1 text-lg font-bold text-slate-950">Tên máy chủ</h2>
                            <p class="mb-0 text-sm text-slate-500">Tên này sẽ hiển thị trong dashboard và dùng làm hostname khi tạo VPS.</p>
                        </div>
                    </div>

                    <div class="max-w-xl">
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Tên máy chủ</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus"
                            placeholder="my-server-01"
                            value="{{ old('name', $defaultName) }}"
                            required
                            pattern="[a-z][a-z0-9\-]{1,30}[a-z0-9]"
                            title="Tên phải bắt đầu bằng chữ thường, dài 3-32 ký tự, chỉ gồm chữ thường, số và dấu gạch ngang, không kết thúc bằng dấu gạch ngang."
                        >
                        <p class="mb-0 mt-2 text-xs text-slate-500">Dùng 3-32 ký tự gồm chữ thường, số và dấu gạch ngang.</p>
                    </div>
                </x-ui.card>

                @if($canAssignVps)
                    <x-ui.card padding="md">
                        <div class="mb-3 flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-danger-600 font-mono text-sm font-bold text-white">A</span>
                            <div>
                                <h2 class="mb-1 text-lg font-bold text-slate-950">Gan VPS cho user</h2>
                                <p class="mb-0 text-sm text-slate-500">Gõ tên, email hoặc ID để tìm user (mỗi lần tối đa 20 kết quả). Chi phí trừ vào số dư tài khoản được chọn.</p>
                            </div>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_180px]">
                            <div>
                                <label for="user_id" class="mb-2 block text-sm font-semibold text-slate-700">Tai khoan nhan VPS</label>
                                <select
                                    x-ref="ownerUserSelect"
                                    id="user_id"
                                    name="user_id"
                                    class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus vps-owner-select"
                                    required
                                >
                                    @if($canAssignVps && $ownerUserPreselect)
                                        <option value="{{ $ownerUserPreselect->id }}" selected>
                                            #{{ $ownerUserPreselect->id }} — {{ $ownerUserPreselect->name }} — {{ $ownerUserPreselect->email }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-xs font-bold uppercase text-slate-500">So du user</div>
                                <div class="mt-1 font-mono text-base font-bold text-success-700" x-text="formatMoney(balance)"></div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                <section>
                    <div class="mb-3 flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 font-mono text-sm font-bold text-white">2</span>
                        <div>
                            <h2 class="mb-1 text-lg font-bold text-slate-950">Chọn gói VPS - Chip "AMD EPYC 9B45" siêu mạnh </h2>
                            <p class="mb-0 text-sm text-slate-500">Gói Pro được tối ưu cho phần lớn website, tool và workload thường gặp.</p>
                        </div>
                    </div>

                    <input type="hidden" name="plan" x-bind:value="plan">
                    <input type="hidden" name="voucher_code" x-bind:value="voucherCode">
                    <div class="vps-plan-grid grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($plans as $key => $plan)
                            <x-vps.pricing-card
                                :id="$key"
                                :plan="$plan"
                                :checked="$selectedPlan === $key"
                                :featured="$key === 'plan_pro'"
                            />
                        @endforeach
                    </div>
                </section>

                <x-ui.card padding="md">
                    <div class="mb-4 flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 font-mono text-sm font-bold text-white">3</span>
                        <div>
                            <h2 class="mb-1 text-lg font-bold text-slate-950">Hệ điều hành và khu vực</h2>
                            <p class="mb-0 text-sm text-slate-500">Ưu tiên khu vực gần Việt Nam để giảm độ trễ.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        <div>
                            <label for="os_image" class="mb-2 block text-sm font-semibold text-slate-700">Hệ điều hành</label>
                            <select id="os_image" name="os_image" class="block min-h-12 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm ui-focus" required>
                                <optgroup label="Windows">
                                    @foreach($windowsImages as $key => $desc)
                                        <option value="{{ $key }}" {{ old('os_image') === $key || (!old('os_image') && str_contains(strtolower($desc), '2022')) ? 'selected' : '' }}>{{ $desc }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Ubuntu">
                                    @if(!empty($ubuntuImages))
                                        @foreach($ubuntuImages as $key => $desc)
                                            <option value="{{ $key }}" {{ old('os_image') === $key ? 'selected' : '' }}>{{ $desc }}</option>
                                        @endforeach
                                    @else
                                        <option value="ubuntu-os-cloud:ubuntu-2204-lts">Ubuntu 22.04 LTS</option>
                                    @endif
                                </optgroup>
                                <optgroup label="Linux">
                                    @foreach($linuxImages as $key => $desc)
                                        <option value="{{ $key }}" {{ old('os_image') === $key ? 'selected' : '' }}>{{ $desc }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <div>
                            <div class="mb-2 text-sm font-semibold text-slate-700">Thời hạn thuê</div>
                            <input type="hidden" name="duration" x-bind:value="duration">
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach($durationOptions as $days => $label)
                                    <button
                                        type="button"
                                        x-bind:disabled="submitting"
                                        class="flex min-h-10 items-center justify-center rounded-lg border px-3 text-center text-sm font-semibold transition-colors"
                                        x-on:click="setDuration({{ $days }})"
                                        x-bind:class="Number(duration) === {{ $days }} ? 'border-brand-600 bg-brand-50 text-brand-700 ring-2 ring-brand-100' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300'"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="mb-2 text-sm font-semibold text-slate-700">Khu vực triển khai</div>
                        <input type="hidden" name="zone" x-bind:value="zone">
                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                            @forelse($uiZones as $prefix => $zoneData)
                                @php
                                    $pingValue = (int) filter_var($zoneData['ping'], FILTER_SANITIZE_NUMBER_INT);
                                    $pingVariant = $pingValue <= 50 ? 'success' : ($pingValue <= 100 ? 'warning' : 'danger');
                                @endphp
                                <button
                                    type="button"
                                    x-bind:disabled="submitting"
                                    class="flex min-h-11 items-center justify-between gap-2 rounded-lg border px-3 text-left text-sm font-semibold transition-colors"
                                    x-on:click="zone = '{{ $zoneData['id'] }}'"
                                    x-bind:class="zone === '{{ $zoneData['id'] }}' ? 'border-brand-600 bg-brand-50 text-brand-700 ring-2 ring-brand-100' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300'"
                                >
                                    <span>{{ $zoneData['flag'] }} {{ $zoneData['name'] }}</span>
                                    <x-ui.badge :variant="$pingVariant" :dot="false">{{ $zoneData['ping'] }}</x-ui.badge>
                                </button>
                            @empty
                                <button
                                    type="button"
                                    x-bind:disabled="submitting"
                                    class="flex min-h-11 items-center justify-between gap-2 rounded-lg border px-3 text-left text-sm font-semibold transition-colors"
                                    x-on:click="zone = 'asia-southeast1-b'"
                                    x-bind:class="zone === 'asia-southeast1-b' ? 'border-brand-600 bg-brand-50 text-brand-700 ring-2 ring-brand-100' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300'"
                                >
                                    <span>Singapore</span>
                                    <x-ui.badge variant="success" :dot="false">30ms</x-ui.badge>
                                </button>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="hidden lg:block">
                <x-vps.checkout-summary
                    :balance="$balance"
                    :deposit-route="route('deposits.index')"
                    :voucher-preview-route="route('vps.voucher.preview')"
                />
            </div>
        </div>

        <div class="fixed inset-x-3 bottom-20 z-[180] lg:hidden">
            <div class="rounded-card border border-slate-200 bg-white p-3 shadow-soft">
                <div class="mb-2 flex gap-2">
                    <input
                        type="text"
                        class="min-h-10 min-w-0 flex-1 rounded-lg border-slate-300 bg-white text-sm font-semibold uppercase shadow-sm ui-focus"
                        placeholder="Voucher"
                        x-model="voucherCode"
                        x-on:input="clearVoucherPreview()"
                    >
                    <button
                        type="button"
                        class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 shadow-sm"
                        x-bind:disabled="voucherLoading || submitting || !voucherCode"
                        x-on:click="previewVoucher()"
                    >
                        <span x-text="voucherLoading ? '...' : 'Ap dung'"></span>
                    </button>
                </div>

                <div class="mb-2 rounded-lg border px-3 py-2 text-xs font-semibold" x-show="voucherMessage" x-bind:class="voucherValid ? 'border-success-100 bg-success-50 text-success-700' : 'border-danger-100 bg-danger-50 text-danger-700'" x-cloak>
                    <span x-text="voucherMessage"></span>
                </div>

                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold text-slate-500" x-text="selectedPlanName || 'Chưa chọn gói'"></div>
                        <div class="truncate text-sm font-bold text-slate-950" x-text="durationLabel + ' · ' + selectedSpecs"></div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-xs font-semibold text-slate-500">Tổng</div>
                        <div class="font-mono text-base font-bold text-brand-700" x-text="formatMoney(totalPrice)"></div>
                    </div>
                </div>

                <div class="mb-2 flex justify-between gap-3 text-xs font-semibold text-success-700" x-show="discountAmount > 0" x-cloak>
                    <span>Giam gia</span>
                    <strong>-<span x-text="formatMoney(discountAmount)"></span></strong>
                </div>

                <div class="mb-2 rounded-lg border border-danger-100 bg-danger-50 px-3 py-2 text-xs font-semibold text-danger-700" x-show="missingAmount > 0" x-cloak>
                    Cần nạp thêm <span x-text="formatMoney(missingAmount)"></span>
                </div>

                <button
                    type="submit"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-brand-600 bg-brand-600 px-4 text-sm font-bold text-white shadow-sm"
                    x-show="missingAmount <= 0"
                    x-bind:disabled="!plan || submitting"
                >
                    <span x-text="submitting ? 'Đang khởi tạo...' : 'Tạo VPS'"></span>
                </button>

                <a
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-success-600 bg-success-600 px-4 text-sm font-bold text-white no-underline shadow-sm"
                    x-show="missingAmount > 0"
                    x-bind:href="'{{ route('deposits.index') }}?amount=' + missingAmount"
                    x-cloak
                >
                    Nạp thêm <span class="ml-1" x-text="formatMoney(missingAmount)"></span>
                </a>
            </div>
        </div>
    </form>

    <div
        class="vps-create-loading-overlay"
        x-show="submitting"
        x-cloak
        role="alert"
        aria-live="assertive"
        aria-busy="true"
    >
        <div class="vps-create-loading-panel">
            <div class="vps-create-loading-spinner" aria-hidden="true"></div>
            <div class="vps-create-loading-title">Đang tạo VPS...</div>
            <div class="vps-create-loading-text">Vui lòng giữ nguyên trang này cho đến khi hoàn tất.</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@if($canAssignVps)
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" crossorigin="anonymous" />
@endif
<style>
    .vps-create-page {
        padding-bottom: 2rem;
    }

    @media (max-width: 1023px) {
        .vps-create-page {
            padding-bottom: 19rem;
        }
    }

    .vps-create-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.68);
        backdrop-filter: blur(6px);
        cursor: wait;
    }

    .vps-create-loading-panel {
        width: min(100%, 360px);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 12px;
        background: #ffffff;
        padding: 28px 24px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
    }

    .vps-create-loading-spinner {
        width: 48px;
        height: 48px;
        margin: 0 auto 18px;
        border: 4px solid #dbeafe;
        border-top-color: #2563eb;
        border-radius: 9999px;
        animation: vps-create-spin 0.8s linear infinite;
    }

    .vps-create-loading-title {
        color: #0f172a;
        font-size: 18px;
        font-weight: 800;
        line-height: 1.35;
    }

    .vps-create-loading-text {
        margin-top: 8px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    @keyframes vps-create-spin {
        to {
            transform: rotate(360deg);
        }
    }

    @if($canAssignVps)
    .vps-create-page .select2-container {
        width: 100% !important;
    }
    .vps-create-page .select2-container--default .select2-selection--single {
        min-height: 48px;
        border-color: #cbd5e1;
        border-radius: 0.5rem;
        padding: 6px 10px;
    }
    .vps-create-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px;
        padding-left: 0;
    }
    .vps-create-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px;
    }
    @endif
</style>
@endpush

@push('scripts')
@if($canAssignVps)
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js" crossorigin="anonymous"></script>
@endif
<script>
    window.vpsCreatePage = function () {
        return {
            plan: @json($selectedPlan),
            zone: @json($selectedZone),
            duration: @json($selectedDuration),
            voucherCode: @json($selectedVoucher),
            ownerUserId: @json((string) $selectedOwnerId),
            ownerBalances: @json($ownerBalances),
            voucherPreviewUrl: @json(route('vps.voucher.preview')),
            csrfToken: @json(csrf_token()),
            ownerUserSearchUrl: @json($canAssignVps ? route('vps.assignable-users.search') : ''),
            plans: @json($planPayload),
            balance: @json($balance),
            submitting: false,
            durations: @json($durationOptions),
            voucherLoading: false,
            voucherValid: false,
            voucherMessage: '',
            discountAmount: 0,
            init() {
                if (!@json($canAssignVps)) {
                    return;
                }
                var self = this;
                queueMicrotask(function () {
                    var el = self.$refs.ownerUserSelect;
                    if (!el || !window.jQuery || !window.jQuery.fn.select2) {
                        return;
                    }
                    var $el = window.jQuery(el);
                    if ($el.data('select2')) {
                        return;
                    }
                    if (!self.ownerUserSearchUrl) {
                        return;
                    }
                    $el.select2({
                        width: '100%',
                        dropdownParent: window.jQuery('.vps-create-page'),
                        placeholder: 'Gõ tên, email hoặc ID...',
                        minimumInputLength: 0,
                        ajax: {
                            url: self.ownerUserSearchUrl,
                            dataType: 'json',
                            delay: 280,
                            data: function (params) {
                                return { q: params.term || '' };
                            },
                            processResults: function (data) {
                                return {
                                    results: data.results || [],
                                    pagination: { more: false },
                                };
                            },
                        },
                    });
                    $el.off('change.vpsOwner').on('change.vpsOwner', function () {
                        var row = $el.select2('data')[0];
                        if (row && typeof row.balance !== 'undefined') {
                            self.ownerBalances = Object.assign({}, self.ownerBalances, {
                                [String(row.id)]: Number(row.balance),
                            });
                        }
                        self.setOwnerUser(window.jQuery(this).val());
                    });
                });
            },
            setOwnerUser: function (value) {
                this.ownerUserId = String(value || '');
                this.balance = Number(this.ownerBalances[this.ownerUserId] || 0);
                this.clearVoucherPreview();
            },
            selectPlan: function (value) {
                this.plan = value;
                this.clearVoucherPreview();
            },
            setDuration: function (value) {
                this.duration = value;
                this.clearVoucherPreview();
            },
            clearVoucherPreview: function () {
                this.voucherValid = false;
                this.voucherMessage = '';
                this.discountAmount = 0;
            },
            get selectedPlan() {
                return this.plans[this.plan] || null;
            },
            get selectedPlanName() {
                return this.selectedPlan ? this.selectedPlan.name : '';
            },
            get selectedSpecs() {
                if (!this.selectedPlan) return '-';
                return this.selectedPlan.cpu + 'C / ' + this.selectedPlan.ram + 'GB / ' + this.selectedPlan.disk + 'GB';
            },
            get durationLabel() {
                return this.durations[this.duration] || (this.duration + ' ngày');
            },
            get subtotalPrice() {
                if (!this.selectedPlan) return 0;
                var total = (Number(this.duration) / 30) * Number(this.selectedPlan.price);
                if (Number(this.duration) === 365) total = total * 0.85;
                return Math.round(total);
            },
            get totalPrice() {
                return Math.max(0, this.subtotalPrice - Number(this.discountAmount || 0));
            },
            get missingAmount() {
                return Math.max(0, this.totalPrice - this.balance);
            },
            formatMoney: function (value) {
                return Number(value || 0).toLocaleString('vi-VN') + ' VND';
            },
            previewVoucher: async function () {
                if (!this.voucherCode || this.voucherLoading) return;

                this.voucherLoading = true;
                this.voucherMessage = '';

                try {
                    const response = await fetch(this.voucherPreviewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({
                            plan: this.plan,
                            duration: this.duration,
                            voucher_code: this.voucherCode,
                            user_id: this.ownerUserId,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.voucherValid = false;
                        this.discountAmount = 0;
                        this.voucherMessage = data.message || 'Voucher khong hop le.';
                        return;
                    }

                    this.voucherValid = true;
                    this.discountAmount = Number(data.discount_amount || 0);
                    this.voucherCode = data.code || this.voucherCode;
                    this.voucherMessage = this.discountAmount > 0
                        ? 'Da giam ' + this.formatMoney(this.discountAmount)
                        : 'Voucher hop le.';
                } catch (error) {
                    this.voucherValid = false;
                    this.discountAmount = 0;
                    this.voucherMessage = 'Khong kiem tra duoc voucher.';
                } finally {
                    this.voucherLoading = false;
                }
            }
        };
    };
</script>
@endpush
