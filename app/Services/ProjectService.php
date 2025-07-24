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
            $broadcastMembers = $allMembers;

            TaskProgress::create([
                'projectId' => $project->id,
                'user_id' => $user->id,
                'pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD,
                'progress' => TaskProgress::INITIAL_PROJECT_PERCENCT,
            ]);

            NewProjectCreated::dispatch(\App\Models\Project::count());

            // Đảm bảo broadcast và notify chỉ chạy sau khi transaction commit thành công
            DB::afterCommit(function () use ($project, $broadcastMembers) {
                foreach ($broadcastMembers as $memberId) {
                    broadcast(new NewProjectForMembers($project, $memberId));
                    // Gửi notification cho member khi được thêm vào project
                    $member = \App\Models\User::find($memberId);
                    if ($member) {
                        $member->notify(new \App\Notifications\NewProjectAssigned($project, $memberId));
                    }
                }
            });

            return ['message' => 'Project created', 'status' => 200];
        });
    }

    public function updateProject($fields, $user)
    {
        return DB::transaction(function () use ($fields, $user) {
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

            // Đảm bảo broadcast và notify chỉ chạy sau khi transaction commit thành công
            DB::afterCommit(function () use ($project, $removedMembers, $allMembers, $newMembers) {
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
            });

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

    /**
     * Pin a project for the current user
     * @param \App\Models\User $user
     * @param array $fields
     * @return array
     */
    public function pinProjectForUser($user, $fields)
    {
        $validator = Validator::make($fields, [
            'projectId' => 'required|numeric|exists:projects,id',
        ]);
        if ($validator->fails()) {
            return [
                'error' => $validator->errors()->first(),
                'code' => 422
            ];
        }
        DB::transaction(function () use ($user, $fields) {
            // Unpin all projects for this user
            \App\Models\TaskProgress::where('user_id', $user->id)
                ->update(['pinned_on_dashboard' => \App\Models\TaskProgress::NOT_PINNED_ON_DASHBOARD]);
            // Pin the selected project for this user
            \App\Models\TaskProgress::where('projectId', $fields['projectId'])
                ->where('user_id', $user->id)
                ->update(['pinned_on_dashboard' => \App\Models\TaskProgress::PINNED_ON_DASHBOARD]);
        });
        return [
            'data' => null,
            'message' => 'Project pinned successfully'
        ];
    }

    /**
     * Get the pinned project for the current user
     * @param \App\Models\User $user
     * @return array
     */
    public function getPinnedProjectForUser($user)
    {
        $project = DB::table('task_progress')
            ->join('projects', 'task_progress.projectId', '=', 'projects.id')
            ->select('projects.id', 'projects.name')
            ->where('task_progress.pinned_on_dashboard', \App\Models\TaskProgress::PINNED_ON_DASHBOARD)
            ->where('task_progress.user_id', $user->id)
            ->first();
        return [
            'data' => $project,
            'message' => 'Get pinned project successfully'
        ];
    }
}
