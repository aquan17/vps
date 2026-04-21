<?php

namespace App\Http\Controllers;

use App\Models\DepositOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminRevenueController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->is_admin, 403);

        $totalRevenue = (int) DepositOrder::where('status', 'paid')->sum('amount');
        $paidOrdersCount = DepositOrder::where('status', 'paid')->count();
        $pendingOrdersCount = DepositOrder::where('status', 'pending')->count();
        $pendingAmount = (int) DepositOrder::where('status', 'pending')->sum('amount');

        $todayRevenue = (int) DepositOrder::where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        $monthRevenue = (int) DepositOrder::where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $lastMonthRevenue = (int) DepositOrder::where('status', 'paid')
            ->whereBetween('paid_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('amount');

        $monthChangePercent = null;
        if ($lastMonthRevenue > 0) {
            $monthChangePercent = round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
        }

        $activeDepositors = DepositOrder::where('status', 'paid')
            ->distinct('user_id')
            ->count('user_id');

        $averagePaidOrder = $paidOrdersCount > 0
            ? (int) round($totalRevenue / $paidOrdersCount)
            : 0;

        $monthlyRevenue = $this->buildMonthlyRevenue();
        $maxMonthlyRevenue = max(array_column($monthlyRevenue, 'total') ?: [0]);

        $topDepositors = DB::table('deposit_orders')
            ->join('users', 'users.id', '=', 'deposit_orders.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('SUM(deposit_orders.amount) as total_amount'),
                DB::raw('COUNT(deposit_orders.id) as order_count'),
                DB::raw('MAX(deposit_orders.paid_at) as last_paid_at')
            )
            ->where('deposit_orders.status', 'paid')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_amount')
            ->limit(8)
            ->get();

        $recentOrders = DepositOrder::with('user')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(12)
            ->get();

        return view('admin.revenue', compact(
            'totalRevenue',
            'todayRevenue',
            'monthRevenue',
            'lastMonthRevenue',
            'monthChangePercent',
            'pendingAmount',
            'paidOrdersCount',
            'pendingOrdersCount',
            'activeDepositors',
            'averagePaidOrder',
            'monthlyRevenue',
            'maxMonthlyRevenue',
            'topDepositors',
            'recentOrders'
        ));
    }

    private function buildMonthlyRevenue(): array
    {
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonthsNoOverflow($i);
            $months[$date->format('Y-m')] = [
                'label' => $date->format('m/Y'),
                'total' => 0,
                'count' => 0,
            ];
        }

        $paidOrders = DepositOrder::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subMonthsNoOverflow(5)->startOfMonth())
            ->get(['amount', 'paid_at']);

        foreach ($paidOrders as $order) {
            $key = $order->paid_at->format('Y-m');
            if (!isset($months[$key])) {
                continue;
            }

            $months[$key]['total'] += (int) $order->amount;
            $months[$key]['count']++;
        }

        return array_values($months);
    }
}
