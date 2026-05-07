<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()
            ->withErrors(['email' => 'Email hoặc mật khẩu không chính xác.'])
            ->onlyInput('email');
    }

    public function showRegister() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if (config('services.turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', 'string'];
        }

        $validated = $request->validate($rules, [
            'cf-turnstile-response.required' => 'Vui lòng xác nhận CAPTCHA trước khi đăng ký.',
        ]);

        if (config('services.turnstile.enabled') && !$this->verifyTurnstile($validated['cf-turnstile-response'] ?? null, $request->ip())) {
            return back()
                ->withErrors(['captcha' => 'Xác minh CAPTCHA thất bại, vui lòng thử lại.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);
        return redirect('dashboard');
    }

    private function verifyTurnstile(?string $token, ?string $ip): bool
    {
        $secret = (string) config('services.turnstile.secret_key');

        if ($secret === '' || !$token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
        } catch (\Throwable $e) {
            return false;
        }

        return (bool) data_get($response->json(), 'success', false);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
