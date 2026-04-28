<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'min_order_amount',
        'usage_limit',
        'used_count',
        'per_user_limit',
        'is_active',
        'starts_at',
        'ends_at',
        'plan_ids',
        'durations',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'plan_ids' => 'array',
        'durations' => 'array',
    ];

    public function redemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
