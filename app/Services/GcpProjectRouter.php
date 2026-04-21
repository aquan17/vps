<?php

namespace App\Services;

use App\Models\GcpProject;

class GcpProjectRouter
{
    /**
     * Tìm kiếm một dự án (móng/pool) còn trống Quota.
     * Thuật toán: chọn project ít VPS nhất trong danh sách đang active và chưa full.
     */
    public function getAvailableProject()
    {
        // Lấy danh sách các tài khoản đang hoạt động & chưa bị đánh dấu FULL
        $projects = GcpProject::withCount([
                'instances as active_instances_count' => function ($query) {
                    $query->where('status', '!=', 'Lỗi API');
                },
            ])
            ->where('is_active', true)
            ->where('is_full', false)
            ->orderBy('active_instances_count')
            ->orderBy('id')
            ->get();

        if ($projects->isEmpty()) {
            throw new \Exception('Tạm hết hàng. Vui lòng quay lại sau.');
        }

        return $projects->first();
    }

    /**
     * Đánh dấu dự án đã cạn kiệt tài nguyên (Quota Exceeded)
     */
    public function markProjectAsFull(int $projectId)
    {
        $project = GcpProject::find($projectId);
        if ($project) {
            $project->is_full = true;
            $project->save();
        }
    }
}
