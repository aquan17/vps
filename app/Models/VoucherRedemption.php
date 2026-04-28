<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'user_id',
        'vps_instance_id',
        'code',
        'discount_amount',
        'original_amount',
        'final_amount',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vpsInstance()
    {
        return $this->belongsTo(VpsInstance::class);
    }
}
