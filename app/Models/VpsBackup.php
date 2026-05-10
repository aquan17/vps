<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'vps_instance_id',
        'gcp_project_id',
        'snapshot_name',
        'source_disk',
        'status',
        'error_message',
        'created_by',
    ];

    public function vps()
    {
        return $this->belongsTo(VpsInstance::class, 'vps_instance_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
