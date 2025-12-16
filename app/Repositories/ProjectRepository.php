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
            'tasks.task_members' => function ($q) {
                $q->with('user:id,name,avatar');
            },
            'task_progress',
            'users:id,name,email,avatar',
            'creator:id,name,email,avatar',
            // boardColumns không cần eager load vì đã được lưu trong JSON field
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
        return $projects->orderBy('created_at', 'desc')->paginate(9);
    }

    /**
     * Count projects for a user (as creator or member)
     * Optimized query using union to avoid duplicate counting
     * 
     * @param int $userId
     * @return int
     */
    public function countProjectsForUser(int $userId): int
    {
        // Use union to get unique project IDs in a single query
        $createdProjectIds = $this->model->where('creator_id', $userId)->select('id');
        
        $memberProjectIds = $this->model->whereHas('users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        })->select('id');
        
        // Union both queries and count distinct IDs
        return $createdProjectIds->union($memberProjectIds)->distinct()->count('id');
    }
}
