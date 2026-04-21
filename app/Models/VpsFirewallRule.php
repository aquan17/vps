<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsFirewallRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vps_instance_id',
        'gcp_project_id',
        'rule_name',
        'target_tag',
        'protocol',
        'port_start',
        'port_end',
        'source_range',
        'sync_status',
        'sync_error',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vpsInstance()
    {
        return $this->belongsTo(VpsInstance::class);
    }

    public function gcpProject()
    {
        return $this->belongsTo(GcpProject::class);
    }

    public function portLabel(): string
    {
        return $this->port_start === $this->port_end
            ? (string) $this->port_start
            : $this->port_start . '-' . $this->port_end;
    }

    public function syncBadgeVariant(): string
    {
        if ($this->sync_status === 'active') {
            return 'success';
        }

        if ($this->sync_status === 'failed') {
            return 'danger';
        }

        return 'warning';
    }
}
