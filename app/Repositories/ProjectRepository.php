<?php

namespace App\Repositories;

use App\Models\Project;

class ProjectRepository
{
    public function create(array $data)
    {
        return Project::create($data);
    }

    public function attachUsers(Project $project, array $userIds)
    {
        $project->users()->syncWithoutDetaching($userIds);
    }

    public function syncUsers(Project $project, array $userIds)
    {
        $project->users()->sync($userIds);
    }

    public function update(Project $project, array $data)
    {
        $project->update($data);
        return $project;
    }
}
