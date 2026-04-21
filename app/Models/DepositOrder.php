<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'amount',
        'status',
        'provider',
        'transaction_ref',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getVietQrUrlAttribute(): string
    {
        return 'https://img.vietqr.io/image/' . config('deposit.bank_id') . '-' . config('deposit.account_no') . '-compact2.png?' . http_build_query([
            'amount' => $this->amount,
            'addInfo' => $this->code,
            'accountName' => config('deposit.account_name'),
        ]);
    }
}
