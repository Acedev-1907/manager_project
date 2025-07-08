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

    public function getBySlugWithRelations($slug)
    {
        return Project::with([
            'tasks' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'tasks.task_members.user',
            'task_progress',
            'users'
        ])->where('slug', $slug)->first();
    }

    public function getProjectsForUser($userId, $query = null)
    {
        $projects = Project::with(['task_progress', 'creator', 'users'])
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        if ($query) {
            $projects->where('name', 'like', '%' . $query . '%');
        }
        return $projects->orderBy('created_at', 'desc')->paginate(6);
    }
}
