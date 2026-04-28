@php
    $selectedPlans = old('plan_ids', $voucher->plan_ids ?? []);
    $selectedDurations = array_map('intval', old('durations', $voucher->durations ?? []));
    $prefix = $voucher ? 'voucher_' . $voucher->id . '_' : 'new_';
    $activeValue = old('is_active', $voucher->is_active ?? true);
@endphp

<div class="voucher-fields">
    <section class="voucher-field-section voucher-field-section--main">
        <div class="voucher-field-section-head">
            <div>
                <h3>Thông tin mã</h3>
                <p>Mã nên ngắn, dễ gõ và chỉ gồm chữ, số, gạch ngang hoặc gạch dưới.</p>
            </div>

            <label class="voucher-switch">
                <input type="checkbox" name="is_active" value="1" {{ $activeValue ? 'checked' : '' }}>
                <span></span>
                Đang bật
            </label>
        </div>

        <div class="voucher-field-grid voucher-field-grid--main">
            <div class="voucher-control voucher-control--code">
                <label for="{{ $prefix }}code">Mã voucher</label>
                <input id="{{ $prefix }}code" type="text" name="code" class="form-control fw-bold text-uppercase" value="{{ old('code', $voucher->code ?? '') }}" placeholder="SALE20" required>
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}type">Kiểu giảm</label>
                <select id="{{ $prefix }}type" name="type" class="form-select" required>
                    <option value="percent" {{ old('type', $voucher->type ?? 'percent') === 'percent' ? 'selected' : '' }}>Phần trăm</option>
                    <option value="fixed" {{ old('type', $voucher->type ?? '') === 'fixed' ? 'selected' : '' }}>Số tiền cố định</option>
                </select>
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}value">Giá trị</label>
                <input id="{{ $prefix }}value" type="number" name="value" min="1" class="form-control fw-bold" value="{{ old('value', $voucher->value ?? '') }}" placeholder="20" required>
            </div>
        </div>
    </section>

    <section class="voucher-field-section">
        <div class="voucher-field-section-head">
            <div>
                <h3>Điều kiện sử dụng</h3>
                <p>Các trường bỏ trống sẽ được xem là không giới hạn.</p>
            </div>
        </div>

        <div class="voucher-field-grid">
            <div class="voucher-control">
                <label for="{{ $prefix }}max_discount">Giảm tối đa</label>
                <input id="{{ $prefix }}max_discount" type="text" name="max_discount" class="form-control admin-voucher-money" value="{{ old('max_discount', isset($voucher->max_discount) ? number_format((int) $voucher->max_discount, 0, ',', '.') : '') }}" placeholder="50.000">
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}min_order_amount">Đơn tối thiểu</label>
                <input id="{{ $prefix }}min_order_amount" type="text" name="min_order_amount" class="form-control admin-voucher-money" value="{{ old('min_order_amount', $voucher ? number_format((int) $voucher->min_order_amount, 0, ',', '.') : '') }}" placeholder="0">
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}usage_limit">Tổng lượt</label>
                <input id="{{ $prefix }}usage_limit" type="number" name="usage_limit" min="1" class="form-control" value="{{ old('usage_limit', $voucher->usage_limit ?? '') }}" placeholder="Không giới hạn">
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}per_user_limit">Lượt / user</label>
                <input id="{{ $prefix }}per_user_limit" type="number" name="per_user_limit" min="1" class="form-control" value="{{ old('per_user_limit', $voucher->per_user_limit ?? 1) }}" required>
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}starts_at">Bắt đầu</label>
                <input id="{{ $prefix }}starts_at" type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $voucher && $voucher->starts_at ? $voucher->starts_at->format('Y-m-d\TH:i') : '') }}">
            </div>

            <div class="voucher-control">
                <label for="{{ $prefix }}ends_at">Kết thúc</label>
                <input id="{{ $prefix }}ends_at" type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $voucher && $voucher->ends_at ? $voucher->ends_at->format('Y-m-d\TH:i') : '') }}">
            </div>
        </div>
    </section>

    <section class="voucher-field-section">
        <div class="voucher-field-section-head">
            <div>
                <h3>Phạm vi áp dụng</h3>
                <p>Không chọn mục nào nghĩa là voucher áp dụng cho tất cả.</p>
            </div>
        </div>

        <div class="voucher-scope-grid">
            <div>
                <div class="voucher-group-label">Gói VPS</div>
                <div class="voucher-tile-grid">
                    @foreach($plans as $key => $plan)
                        <label class="voucher-tile">
                            <input type="checkbox" name="plan_ids[]" value="{{ $key }}" {{ in_array($key, $selectedPlans, true) ? 'checked' : '' }}>
                            <i aria-hidden="true"></i>
                            <span>
                                <strong>{{ $plan['name'] }}</strong>
                                <small>{{ $plan['cores'] ?? '-' }}C / {{ $plan['ram'] ?? '-' }}GB</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="voucher-group-label">Thời hạn</div>
                <div class="voucher-tile-grid voucher-tile-grid--compact">
                    @foreach($durations as $days => $label)
                        <label class="voucher-tile">
                            <input type="checkbox" name="durations[]" value="{{ $days }}" {{ in_array((int) $days, $selectedDurations, true) ? 'checked' : '' }}>
                            <i aria-hidden="true"></i>
                            <span>
                                <strong>{{ $label }}</strong>
                                <small>{{ $days }} ngày</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
