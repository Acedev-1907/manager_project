<?php

namespace App\Services;

use App\Repositories\ProjectRepository;
use App\Models\TaskProgress;
use App\Events\NewProjectCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\NewProjectForMembers;

class ProjectService
{
    protected $repo;

    public function __construct(ProjectRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createProject($fields, $user)
    {
        $errs = Validator::make($fields, [
            'name' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
        ]);
        if ($errs->fails()) return ['errors' => $errs->errors()->all(), 'status' => 422];

        return DB::transaction(function () use ($fields, $user) {
            $project = $this->repo->create([
                'name' => $fields['name'],
                'startDate' => $fields['startDate'],
                'endDate' => $fields['endDate'],
                'status' => \App\Models\Project::NOT_STARTED,
                'slug' => \App\Models\Project::createSlug($fields['name']),
                'creator_id' => $user->id,
            ]);

            $this->repo->attachUsers($project, [$user->id]);

            $members = $fields['members'] ?? [];
            if (!empty($members) && is_array($members)) {
                $members = array_diff($members, [$user->id]);
                if (!empty($members)) {
                    $this->repo->attachUsers($project, $members);
                }
            }

            $allMembers = array_unique(array_merge($members, [$user->id]));
            foreach ($allMembers as $memberId) {
                broadcast(new NewProjectForMembers($project, $memberId));
            }

            TaskProgress::create([
                'projectId' => $project->id,
                'pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD,
                'progress' => TaskProgress::INITIAL_PROJECT_PERCENCT,
            ]);

            NewProjectCreated::dispatch(\App\Models\Project::count());

            return ['message' => 'Project created', 'status' => 200];
        });
    }

    public function updateProject($fields, $user)
    {
        return \DB::transaction(function () use ($fields, $user) {
            $project = $this->repo->find($fields['id']);
            if (!$project) {
                return ['errors' => ['Project không tồn tại'], 'status' => 404];
            }
            if ($project->creator_id !== $user->id) {
                return ['errors' => ['Bạn không thể edit project này'], 'status' => 403];
            }
            $this->repo->updateById($fields['id'], [
                'name' => $fields['name'],
                'startDate' => $fields['startDate'],
                'endDate' => $fields['endDate'],
            ]);

            // Đồng bộ thành viên (bao gồm cả creator, không trùng lặp)
            $members = $fields['members'] ?? [];
            $allMembers = array_unique(array_merge($members, [$user->id]));
            $this->repo->syncUsers($project, $allMembers);

            // Broadcast event cho tất cả thành viên
            foreach ($allMembers as $memberId) {
                broadcast(new \App\Events\NewProjectForMembers($project, $memberId));
            }

            return ['message' => 'Project updated', 'status' => 200];
        });
    }

    public function getProjectBySlug($slug)
    {
        return $this->repo->getBySlugWithRelations($slug);
    }

    public function getProjectsForUser($userId, $query = null)
    {
        return $this->repo->getProjectsForUser($userId, $query);
    }
}
