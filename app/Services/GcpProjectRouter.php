<?php

namespace App\Services;

use App\Models\GcpProject;

class GcpProjectRouter
{
    public function getAvailableProjects()
    {
        return GcpProject::withCount([
                'instances as active_instances_count' => function ($query) {
                    $query->where('status', '!=', 'Lỗi API');
                },
            ])
            ->where('is_active', true)
            ->where('is_full', false)
            ->orderBy('active_instances_count')
            ->orderBy('id')
            ->get();
    }

    public function getAvailableProject()
    {
        $projects = $this->getAvailableProjects();

        if ($projects->isEmpty()) {
            throw new \Exception('Tạm hết hàng. Vui lòng quay lại sau.');
        }

        return $projects->first();
    }

    public function markProjectAsFull(int $projectId)
    {
        $project = GcpProject::find($projectId);
        if ($project) {
            $project->is_full = true;
            $project->save();
        }
    }
}
