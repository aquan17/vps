<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsInstance extends Model
{
    use HasFactory;

    private const RUNNING_STATUSES = [
        'Sẵn sàng',
        'Đang chạy',
        'RUNNING',
    ];

    private const PROVISIONING_STATUSES = [
        'Đang khởi tạo...',
        'Dang khoi tao...',
        'Đang cài RDP...',
        'Đang cài SSH...',
        'PROVISIONING',
        'STAGING',
    ];

    protected $fillable = [
        'user_id',
        'gcp_project_id',
        'name',
        'password',
        'zone',
        'machine_type',
        'gcp_id',
        'status',
        'expires_at',
        'public_ip',
        'os',
        'cpu',
        'ram',
        'disk',
        'voucher_code',
        'original_price',
        'discount_amount',
        'paid_amount',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'password' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gcpProject()
    {
        return $this->belongsTo(GcpProject::class);
    }

    public function firewallRules()
    {
        return $this->hasMany(VpsFirewallRule::class);
    }

    public function voucherRedemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeProvisioning(Builder $query): Builder
    {
        return $query->whereIn('status', self::PROVISIONING_STATUSES);
    }

    public function isProvisioning(): bool
    {
        return in_array($this->status, self::PROVISIONING_STATUSES, true);
    }

    public function statusBadgeClass(): string
    {
        if (in_array($this->status, self::RUNNING_STATUSES, true)) {
            return 'text-bg-success';
        }

        if (str_contains($this->status, 'Lỗi') || str_contains($this->status, 'Lá»—i')) {
            return 'text-bg-danger';
        }

        return 'text-bg-warning';
    }
}
