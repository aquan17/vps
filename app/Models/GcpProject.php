<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GcpProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'credentials_file',
        'is_active',
        'is_full',
    ];

    public function instances()
    {
        return $this->hasMany(VpsInstance::class);
    }
}
