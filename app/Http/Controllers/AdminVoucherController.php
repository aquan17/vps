<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Services\VpsPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminVoucherController extends Controller
{
    private VpsPricingService $pricingService;

    public function __construct(VpsPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function index()
    {
        abort_unless(Auth::user()->is_admin, 403);

        $plans = $this->pricingService->getPlans();
        $durations = $this->durationOptions();

        $vouchers = Voucher::query()
            ->withCount('redemptions')
            ->withSum('redemptions as discount_total', 'discount_amount')
            ->latest()
            ->paginate(12);

        $recentRedemptions = VoucherRedemption::with(['voucher', 'user', 'vpsInstance'])
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'total' => Voucher::count(),
            'active' => Voucher::where('is_active', true)->count(),
            'used' => VoucherRedemption::count(),
            'discount' => (int) VoucherRedemption::sum('discount_amount'),
        ];

        return view('admin.vouchers', compact('plans', 'durations', 'vouchers', 'recentRedemptions', 'stats'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $data = $this->validateVoucher($request);
        $data['code'] = strtoupper($data['code']);

        Voucher::create($data);

        return back()->with('success', 'Da tao voucher ' . $data['code'] . '.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $data = $this->validateVoucher($request, $voucher);
        $data['code'] = strtoupper($data['code']);

        $voucher->update($data);

        return back()->with('success', 'Da cap nhat voucher ' . $voucher->code . '.');
    }

    public function toggle(Voucher $voucher)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $voucher->update(['is_active' => !$voucher->is_active]);

        return back()->with('success', ($voucher->is_active ? 'Da bat ' : 'Da tat ') . $voucher->code . '.');
    }

    public function destroy(Voucher $voucher)
    {
        abort_unless(Auth::user()->is_admin, 403);

        if ($voucher->redemptions()->exists()) {
            throw ValidationException::withMessages([
                'voucher' => 'Voucher da co luot su dung nen khong the xoa. Hay tat voucher neu khong muon dung tiep.',
            ]);
        }

        $code = $voucher->code;
        $voucher->delete();

        return back()->with('success', 'Da xoa voucher ' . $code . '.');
    }

    private function validateVoucher(Request $request, ?Voucher $voucher = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('vouchers', 'code')->ignore($voucher),
            ],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'string', 'max:30'],
            'min_order_amount' => ['nullable', 'string', 'max:30'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['string'],
            'durations' => ['nullable', 'array'],
            'durations.*' => ['integer', Rule::in(array_keys($this->durationOptions()))],
        ]);

        if ($data['type'] === 'percent' && (int) $data['value'] > 100) {
            throw ValidationException::withMessages([
                'value' => 'Voucher phan tram khong duoc vuot qua 100%.',
            ]);
        }

        $allowedPlanIds = array_keys($this->pricingService->getPlans());
        $data['plan_ids'] = array_values(array_intersect($data['plan_ids'] ?? [], $allowedPlanIds));
        $data['durations'] = array_map('intval', $data['durations'] ?? []);
        $data['max_discount'] = $this->moneyToInt($data['max_discount'] ?? null) ?: null;
        $data['min_order_amount'] = $this->moneyToInt($data['min_order_amount'] ?? null);
        $data['usage_limit'] = $data['usage_limit'] ?? null;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['plan_ids'])) {
            $data['plan_ids'] = null;
        }

        if (empty($data['durations'])) {
            $data['durations'] = null;
        }

        return $data;
    }

    private function moneyToInt(?string $value): int
    {
        return (int) preg_replace('/\D/', '', (string) $value);
    }

    private function durationOptions(): array
    {
        return [
            1 => '1 ngay',
            7 => '7 ngay',
            30 => '1 thang',
            90 => '3 thang',
            180 => '6 thang',
            365 => '1 nam',
        ];
    }
}
