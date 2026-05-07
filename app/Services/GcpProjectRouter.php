<?php

namespace App\Services;

use App\Models\GcpProject;

class GcpProjectRouter
{
    public function getAvailableProjects()
    {
        return GcpProject::query()
            ->where('is_active', true)
            ->where('is_full', false)
            ->orderBy('id', 'asc')
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
