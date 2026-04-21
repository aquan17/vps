<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsInstance extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'password'   => 'encrypted',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

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

    // ─── Query Scopes ─────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeProvisioning(Builder $query): Builder
    {
        return $query->whereIn('status', ['Đang khởi tạo...', 'PROVISIONING', 'STAGING']);
    }

    // ─── Presentation Helpers ─────────────────────────────────────────────────

    public function statusBadgeClass(): string
    {
        if (in_array($this->status, ['Đang chạy', 'RUNNING'])) {
            return 'text-bg-success';
        }

        if (str_contains($this->status, 'Lỗi')) {
            return 'text-bg-danger';
        }

        return 'text-bg-warning';
    }
}
