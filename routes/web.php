<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VpsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminRevenueController;

Route::get('/', function () {
    return view('welcome');
});

// Fallback fix for Laravel auto-redirect to /home after login
Route::get('/home', function () {
    return redirect()->route('vps.dashboard');
});

// ─── Guest Routes ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile',           [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/password',  [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Deposits
    Route::get('/deposits',       [DepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits',      [DepositController::class, 'store'])->name('deposits.store');
    Route::get('/deposits/{id}',  [DepositController::class, 'show'])->name('deposits.show');

    // VPS — Dashboard & Create
    Route::get('/dashboard',   [VpsController::class, 'index'])->name('vps.dashboard');
    Route::get('/vps/create',  [VpsController::class, 'create'])->name('vps.create');
    Route::post('/vps',        [VpsController::class, 'store'])->name('vps.store');

    // VPS — Instance management
    Route::get('/vps/{id}',              [VpsController::class, 'show'])->name('vps.show');
    Route::get('/vps/{id}/credentials',  [VpsController::class, 'credentials'])->name('vps.credentials');
    Route::post('/vps/{id}/reboot',      [VpsController::class, 'reboot'])->name('vps.reboot');
    Route::post('/vps/{id}/upgrade',     [VpsController::class, 'upgrade'])->name('vps.upgrade');
    Route::post('/vps/{id}/password',    [VpsController::class, 'updatePassword'])->name('vps.password');
    Route::post('/vps/{id}/renew',       [VpsController::class, 'renew'])->name('vps.renew');
    Route::post('/vps/{id}/firewall',    [VpsController::class, 'openFirewallPort'])->middleware('throttle:10,1')->name('vps.firewall.open');
    Route::delete('/vps/{id}/firewall/{rule}', [VpsController::class, 'deleteFirewallRule'])->middleware('throttle:10,1')->name('vps.firewall.delete');
    Route::delete('/vps/{id}',           [VpsController::class, 'destroy'])->name('vps.destroy');

    // Admin — GCP Project Management (auth + admin check is inside the controller)
    Route::get('/admin/revenue', [AdminRevenueController::class, 'index'])->name('admin.revenue');
    Route::get('/admin/google-cloud',                    [VpsController::class, 'adminGoogleCloud'])->name('admin.google-cloud');
    Route::post('/admin/google-cloud',                   [VpsController::class, 'adminGcpStore'])->name('admin.gcloud.store');
    Route::post('/admin/google-cloud/sync',              [VpsController::class, 'adminGcpSync'])->name('admin.gcloud.sync');
    Route::patch('/admin/google-cloud/{id}/toggle',      [VpsController::class, 'adminGcpToggle'])->name('admin.gcloud.toggle');
});

// ─── Webhook (public, rate-limited) ──────────────────────────────────────────
Route::post('/webhooks/deposits', [DepositController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('webhooks.deposits');
