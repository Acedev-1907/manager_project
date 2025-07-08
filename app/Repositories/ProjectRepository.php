<?php

namespace App\Repositories;

use App\Models\Project;

class ProjectRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Project::class;
    }

    public function attachUsers(Project $project, array $userIds)
    {
        $project->users()->syncWithoutDetaching($userIds);
    }

    public function syncUsers(Project $project, array $userIds)
    {
        $project->users()->sync($userIds);
    }

    public function getBySlugWithRelations($slug)
    {
        return $this->model->with([
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
        $projects = $this->model->with(['task_progress', 'creator', 'users'])
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        if ($query) {
            $projects->where('name', 'like', '%' . $query . '%');
        }
        return $projects->orderBy('created_at', 'desc')->paginate(6);
    }
}
