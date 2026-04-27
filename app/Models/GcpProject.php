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

    /**
     * Lấy đường dẫn đầy đủ của file credentials, hỗ trợ cả đường dẫn tuyệt đối và tên file.
     */
    public function getCredentialsPathAttribute()
    {
        $path = $this->credentials_file;
        if (!$path) return null;

        // Nếu là đường dẫn tuyệt đối (Windows hoặc Linux)
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        // Nếu chỉ là tên file, tìm trong gcp_credentials
        return storage_path('app/gcp_credentials/' . $path);
    }
}
