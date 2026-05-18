<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VpsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminRevenueController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVoucherController;

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
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware(['throttle:3,1', 'throttle:15,60']);
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
    Route::get('/deposits/{id}/status', [DepositController::class, 'status'])->name('deposits.status');

    // VPS — Dashboard & Create
    Route::get('/dashboard',   [VpsController::class, 'index'])->name('vps.dashboard');
    Route::get('/vps/create',  [VpsController::class, 'create'])->name('vps.create');
    Route::get('/vps/assignable-users/search', [VpsController::class, 'searchAssignableUsers'])
        ->middleware('throttle:90,1')
        ->name('vps.assignable-users.search');
    Route::post('/vps/voucher/preview', [VpsController::class, 'previewVoucher'])->middleware('throttle:30,1')->name('vps.voucher.preview');
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
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::patch('/admin/users/{user}/balance', [AdminUserController::class, 'updateBalance'])->name('admin.users.balance');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->middleware('throttle:30,1')->name('admin.users.destroy');
    Route::get('/admin/vouchers', [AdminVoucherController::class, 'index'])->name('admin.vouchers.index');
    Route::post('/admin/vouchers', [AdminVoucherController::class, 'store'])->name('admin.vouchers.store');
    Route::put('/admin/vouchers/{voucher}', [AdminVoucherController::class, 'update'])->name('admin.vouchers.update');
    Route::patch('/admin/vouchers/{voucher}/toggle', [AdminVoucherController::class, 'toggle'])->name('admin.vouchers.toggle');
    Route::delete('/admin/vouchers/{voucher}', [AdminVoucherController::class, 'destroy'])->name('admin.vouchers.destroy');
    Route::get('/admin/google-cloud',                    [VpsController::class, 'adminGoogleCloud'])->name('admin.google-cloud');
    Route::post('/admin/google-cloud',                   [VpsController::class, 'adminGcpStore'])->name('admin.gcloud.store');
    Route::post('/admin/google-cloud/sync',              [VpsController::class, 'adminGcpSync'])->name('admin.gcloud.sync');
    Route::post('/admin/google-cloud/sync-vps',          [VpsController::class, 'adminGcpSyncVps'])->name('admin.gcloud.sync-vps');
    Route::patch('/admin/google-cloud/vps/{id}/expires-at', [VpsController::class, 'adminVpsExpiresAt'])->name('admin.gcloud.vps.expires-at');
    Route::patch('/admin/google-cloud/{id}/toggle',      [VpsController::class, 'adminGcpToggle'])->name('admin.gcloud.toggle');
    Route::patch('/admin/vps/{id}/backup-toggle',        [VpsController::class, 'adminToggleBackup'])->name('admin.vps.backup.toggle');
    Route::patch('/admin/vps/{id}/backup-policy',        [VpsController::class, 'adminUpdateBackupPolicy'])->name('admin.vps.backup.policy');
    Route::post('/admin/vps/{id}/backups',               [VpsController::class, 'adminCreateBackup'])->name('admin.vps.backups.create');
    Route::delete('/admin/vps/{id}/backups/{backupId}',  [VpsController::class, 'adminDeleteBackup'])->name('admin.vps.backups.delete');
});

// ─── Webhook (public, rate-limited) ──────────────────────────────────────────
Route::post('/webhooks/deposits', [DepositController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('webhooks.deposits');
