<?php

namespace App\Http\Controllers;

use App\Models\DepositOrder;
use App\Models\User;
use App\Models\VpsInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('id', $search);
                });
            })
            ->withCount([
                'vpsInstances as vps_count',
                'vpsInstances as running_vps_count' => function ($query) {
                    $query->whereIn('status', ['Sẵn sàng', 'Đang chạy', 'RUNNING']);
                },
                'depositOrders as deposit_count',
            ])
            ->withSum(['depositOrders as paid_deposit_total' => function ($query) {
                $query->where('status', 'paid');
            }], 'amount')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'users' => User::count(),
            'balance' => (int) User::sum('balance'),
            'vps' => VpsInstance::count(),
            'paid' => (int) DepositOrder::where('status', 'paid')->sum('amount'),
        ];

        return view('admin.users', compact('users', 'stats', 'search'));
    }

    public function updateBalance(Request $request, User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $data = $request->validate([
            'amount' => ['required', 'string', 'max:30'],
        ]);

        $amount = (int) preg_replace('/\D/', '', $data['amount']);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền phải lớn hơn 0 VND.',
            ]);
        }

        DB::transaction(function () use ($user, $data, $amount) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedUser->balance = $amount;
            $lockedUser->save();
        });

        return back()->with('success', 'Đã cập nhật số dư cho ' . $user->name . '.');
    }
}
