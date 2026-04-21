<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\DepositOrder;
use App\Models\VpsInstance;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $vpsCount = VpsInstance::where('user_id', $user->id)->count();
        $runningVpsCount = VpsInstance::where('user_id', $user->id)
            ->whereIn('status', ['Đang chạy', 'RUNNING'])
            ->count();
        $depositCount = DepositOrder::where('user_id', $user->id)->count();
        $paidDepositTotal = DepositOrder::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');

        return view('profile.show', compact(
            'user',
            'vpsCount',
            'runningVpsCount',
            'depositCount',
            'paidDepositTotal'
        ));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.'])
                ->onlyInput('current_password');
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        $request->session()->regenerate();

        return back()->with('success', 'Đã đổi mật khẩu tài khoản thành công.');
    }
}
