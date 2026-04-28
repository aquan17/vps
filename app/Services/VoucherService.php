<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class VoucherService
{
    public function normalizeCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : $code;
    }

    public function preview(?string $code, User $user, string $planId, int $duration, int $subtotal): array
    {
        $normalizedCode = $this->normalizeCode($code);

        if (!$normalizedCode) {
            return $this->emptyResult($subtotal);
        }

        $voucher = $this->findVoucher($code);

        if (!$voucher) {
            throw new InvalidArgumentException('Ma voucher khong ton tai.');
        }

        $this->assertUsable($voucher, $user, $planId, $duration, $subtotal);

        $discount = $this->calculateDiscount($voucher, $subtotal);

        return [
            'voucher' => $voucher,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'original_amount' => $subtotal,
            'final_amount' => max(0, $subtotal - $discount),
        ];
    }

    public function redeem(?string $code, User $user, string $planId, int $duration, int $subtotal, int $vpsInstanceId): array
    {
        $normalizedCode = $this->normalizeCode($code);

        if (!$normalizedCode) {
            return $this->emptyResult($subtotal);
        }

        $voucher = Voucher::where('code', $normalizedCode)->lockForUpdate()->first();

        if (!$voucher) {
            throw new InvalidArgumentException('Ma voucher khong ton tai.');
        }

        $this->assertUsable($voucher, $user, $planId, $duration, $subtotal);

        $discount = $this->calculateDiscount($voucher, $subtotal);
        $finalAmount = max(0, $subtotal - $discount);

        VoucherRedemption::create([
            'voucher_id' => $voucher->id,
            'user_id' => $user->id,
            'vps_instance_id' => $vpsInstanceId,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'original_amount' => $subtotal,
            'final_amount' => $finalAmount,
        ]);

        $voucher->increment('used_count');

        return [
            'voucher' => $voucher,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'original_amount' => $subtotal,
            'final_amount' => $finalAmount,
        ];
    }

    private function findVoucher(?string $code): ?Voucher
    {
        $normalizedCode = $this->normalizeCode($code);

        if (!$normalizedCode) {
            return null;
        }

        return Voucher::where('code', $normalizedCode)->first();
    }

    private function assertUsable(Voucher $voucher, User $user, string $planId, int $duration, int $subtotal): void
    {
        $now = Carbon::now();

        if (!$voucher->is_active) {
            throw new InvalidArgumentException('Voucher da bi tat.');
        }

        if ($voucher->starts_at && $voucher->starts_at->gt($now)) {
            throw new InvalidArgumentException('Voucher chua den thoi gian su dung.');
        }

        if ($voucher->ends_at && $voucher->ends_at->lt($now)) {
            throw new InvalidArgumentException('Voucher da het han.');
        }

        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            throw new InvalidArgumentException('Voucher da het luot su dung.');
        }

        if ($subtotal < (int) $voucher->min_order_amount) {
            throw new InvalidArgumentException('Don hang chua dat gia tri toi thieu de dung voucher.');
        }

        if ($voucher->plan_ids && !in_array($planId, $voucher->plan_ids, true)) {
            throw new InvalidArgumentException('Voucher khong ap dung cho goi VPS nay.');
        }

        if ($voucher->durations && !in_array($duration, array_map('intval', $voucher->durations), true)) {
            throw new InvalidArgumentException('Voucher khong ap dung cho thoi han nay.');
        }

        $usedByUser = VoucherRedemption::where('voucher_id', $voucher->id)
            ->where('user_id', $user->id)
            ->count();

        if ($usedByUser >= (int) $voucher->per_user_limit) {
            throw new InvalidArgumentException('Tai khoan nay da dung voucher toi da so lan cho phep.');
        }
    }

    private function calculateDiscount(Voucher $voucher, int $subtotal): int
    {
        if ($subtotal <= 0) {
            return 0;
        }

        if ($voucher->type === 'percent') {
            $discount = (int) round($subtotal * ((int) $voucher->value / 100));
        } else {
            $discount = (int) $voucher->value;
        }

        if ($voucher->max_discount !== null) {
            $discount = min($discount, (int) $voucher->max_discount);
        }

        return max(0, min($subtotal, $discount));
    }

    private function emptyResult(int $subtotal): array
    {
        return [
            'voucher' => null,
            'code' => null,
            'discount_amount' => 0,
            'original_amount' => $subtotal,
            'final_amount' => $subtotal,
        ];
    }
}
