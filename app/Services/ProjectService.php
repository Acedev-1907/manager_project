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

                // Gửi notification cho member khi được thêm vào project
                $member = \App\Models\User::find($memberId);
                if ($member) {
                    $member->notify(new \App\Notifications\NewProjectAssigned($project, $memberId));
                }
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

            // Lấy danh sách user cũ trước khi update
            $oldMembers = $project->users->pluck('id')->toArray();

            $this->repo->updateById($fields['id'], [
                'name' => $fields['name'],
                'startDate' => $fields['startDate'],
                'endDate' => $fields['endDate'],
            ]);

            $members = $fields['members'] ?? [];
            $allMembers = array_unique(array_merge($members, [$user->id]));
            $this->repo->syncUsers($project, $allMembers);

            // Xác định user bị remove
            $removedMembers = array_diff($oldMembers, $allMembers);

            // Xác định user mới được thêm vào
            $newMembers = array_diff($allMembers, $oldMembers);

            // Broadcast cho user bị remove
            foreach ($removedMembers as $removedId) {
                broadcast(new \App\Events\UserRemovedFromProject($project, $removedId));
            }

            foreach ($allMembers as $memberId) {
                broadcast(new NewProjectForMembers($project, $memberId));
            }

            // Chỉ gửi notification cho thành viên mới (không gửi cho creator)
            foreach ($newMembers as $memberId) {
                if ($memberId == $project->creator_id) continue;
                $member = \App\Models\User::find($memberId);
                if ($member) {
                    $member->notify(new \App\Notifications\NewProjectAssigned($project, $memberId));
                }
            }

            return ['message' => 'Project updated', 'status' => 200];
        });
    }

    /**
     * Delete a project and all related data if the user is the creator.
     *
     * @param int $projectId
     * @param \App\Models\User $user
     * @return array
     */
    public function deleteProject($projectId, $user)
    {
        $project = $this->repo->find($projectId);
        if (!$project) {
            return ['errors' => ['Project does not exist'], 'status' => 404];
        }
        if ($project->creator_id !== $user->id) {
            return ['errors' => ['You cannot delete this project'], 'status' => 403];
        }
        // Broadcast UserRemovedFromProject for all members except creator
        $memberIds = $project->users->pluck('id')->filter(fn($id) => $id !== $user->id);
        foreach ($memberIds as $memberId) {
            broadcast(new \App\Events\UserRemovedFromProject($project, $memberId));
        }
        $project->delete();
        return ['message' => 'Project and related data deleted successfully', 'status' => 200];
    }

    public function getProjectBySlug($slug)
    {
        return $this->repo->getBySlugWithRelations($slug);
    }

    public function getProjectsForUser($userId, $query = null)
    {
        return $this->repo->getProjectsForUser($userId, $query);
    }

    public function countProjectsForUser($userId)
    {
        return $this->repo->countProjectsForUser($userId);
    }
}
