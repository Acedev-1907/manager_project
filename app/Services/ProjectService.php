<?php

namespace App\Services;

use App\Repositories\ProjectRepository;
use App\Models\TaskProgress;
use App\Events\UserProjectCountUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\NewProjectForMembers;

/**
 * Project Service
 * 
 * Handles all business logic related to project operations including
 * creation, updates, deletion, and project analytics.
 */
class ProjectService
{
    protected ProjectRepository $repo;

    public function __construct(ProjectRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Create project data array from fields or DTO
     * 
     * @param array|\App\DTOs\ProjectDTO $data
     * @param \App\Models\User $user
     * @return array
     */
    private function prepareProjectData($data, $user): array
    {
        if ($data instanceof \App\DTOs\ProjectDTO) {
            return [
                'name' => $data->name,
                'startDate' => $data->startDate,
                'endDate' => $data->endDate,
                'content' => $data->content,
                'status' => $data->status ?? \App\Models\Project::NOT_STARTED,
                'slug' => $data->slug ?? \App\Models\Project::createSlug($data->name),
                'creator_id' => $user->id,
            ];
        }

        return [
            'name' => $data['name'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'content' => $data['content'] ?? null,
            'status' => \App\Models\Project::NOT_STARTED,
            'slug' => \App\Models\Project::createSlug($data['name']),
            'creator_id' => $user->id,
        ];
    }

    /**
     * Get members list from fields or DTO
     * 
     * @param array|\App\DTOs\ProjectDTO $data
     * @return array
     */
    private function getMembersList($data): array
    {
        if ($data instanceof \App\DTOs\ProjectDTO) {
            return $data->members ?? [];
        }
        return $data['members'] ?? [];
    }

    /**
     * Create project with members and setup
     * 
     * @param array $projectData
     * @param array $members
     * @param \App\Models\User $user
     * @return \App\Models\Project
     */
    private function createProjectWithMembers(array $projectData, array $members, $user): \App\Models\Project
    {
        $project = $this->repo->create($projectData);

        // Attach creator to project
        $this->repo->attachUsers($project, [$user->id]);

        // Handle additional members
        if (!empty($members) && is_array($members)) {
            $members = array_diff($members, [$user->id]);
            if (!empty($members)) {
                $this->repo->attachUsers($project, $members);
            }
        }

        // Create task progress record for creator
        TaskProgress::create([
            'projectId' => $project->id,
            'user_id' => $user->id,
            'pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD,
            'progress' => TaskProgress::INITIAL_PROJECT_PERCENCT,
        ]);

        return $project;
    }

    /**
     * Create a new project with members (Legacy - array based)
     * 
     * @param array $fields Project data
     * @param \App\Models\User $user Creator user
     * @return array
     */
    public function createProject(array $fields, $user): array
    {
        $validator = Validator::make($fields, [
            'name' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            'content' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()->all(), 'status' => 422];
        }

        return DB::transaction(function () use ($fields, $user) {
            $projectData = $this->prepareProjectData($fields, $user);
            $members = $this->getMembersList($fields);
            
            $project = $this->createProjectWithMembers($projectData, $members, $user);
            $allMembers = array_unique(array_merge($members, [$user->id]));

            // Broadcast events after transaction commit
            DB::afterCommit(function () use ($project, $allMembers) {
                $this->broadcastProjectEvents($project, $allMembers);
            });

            return ['message' => 'Project created', 'status' => 200];
        });
    }

    /**
     * Create a new project with DTO (New - recommended)
     * 
     * @param \App\DTOs\ProjectDTO $dto Project data transfer object
     * @param \App\Models\User $user Creator user
     * @return array
     */
    public function createProjectWithDTO(\App\DTOs\ProjectDTO $dto, $user): array
    {
        return DB::transaction(function () use ($dto, $user) {
            $projectData = $this->prepareProjectData($dto, $user);
            $members = $this->getMembersList($dto);
            
            $project = $this->createProjectWithMembers($projectData, $members, $user);
            $allMembers = array_unique(array_merge($members, [$user->id]));

            // Broadcast events after transaction commit
            DB::afterCommit(function () use ($project, $allMembers) {
                $this->broadcastProjectEvents($project, $allMembers);
            });

            return ['message' => 'Project created', 'status' => 200];
        });
    }

    /**
     * Update an existing project
     * 
     * @param array $fields Updated project data
     * @param \App\Models\User $user User performing the update
     * @return array
     */
    public function updateProject(array $fields, $user): array
    {
        return DB::transaction(function () use ($fields, $user) {
            $project = $this->repo->find($fields['id']);

            // Get old members before update
            $oldMembers = $project->users->pluck('id')->toArray();

            // Update project data
            $this->repo->updateById($fields['id'], [
                'name' => $fields['name'],
                'startDate' => $fields['startDate'],
                'endDate' => $fields['endDate'],
                'content' => $fields['content'] ?? null,
            ]);

            // Handle member updates
            $members = $fields['members'] ?? [];
            $allMembers = array_unique(array_merge($members, [$user->id]));
            $this->repo->syncUsers($project, $allMembers);

            // Determine removed and new members
            $removedMembers = array_diff($oldMembers, $allMembers);
            $newMembers = array_diff($allMembers, $oldMembers);

            // Broadcast events after transaction commit
            DB::afterCommit(function () use ($project, $removedMembers, $allMembers, $newMembers) {
                $this->broadcastProjectUpdateEvents($project, $removedMembers, $allMembers, $newMembers);
            });

            return ['message' => 'Project updated', 'status' => 200];
        });
    }

    /**
     * Delete a project and all related data
     * 
     * @param int $projectId
     * @param \App\Models\User $user
     * @return array
     */
    public function deleteProject(int $projectId, $user): array
    {
        $project = $this->repo->find($projectId);

        if (!$project) {
            return ['errors' => ['Project does not exist'], 'status' => 404];
        }

        if ($project->creator_id !== $user->id) {
            return ['errors' => ['You cannot delete this project'], 'status' => 403];
        }

        // Broadcast removal events for all members except creator
        $memberIds = $project->users->pluck('id')->filter(fn($id) => $id !== $user->id);
        foreach ($memberIds as $memberId) {
            broadcast(new \App\Events\UserRemovedFromProject($project, $memberId));
        }

        $project->delete();

        return ['message' => 'Project and related data deleted successfully', 'status' => 200];
    }

    /**
     * Get project by slug with relations
     * 
     * @param string $slug
     * @return \App\Models\Project|null
     */
    public function getProjectBySlug(string $slug)
    {
        return $this->repo->getBySlugWithRelations($slug);
    }

    /**
     * Get all projects for a specific user
     * 
     * @param int $userId
     * @param string|null $query Search query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProjectsForUser(int $userId, ?string $query = null)
    {
        return $this->repo->getProjectsForUser($userId, $query);
    }

    /**
     * Count projects for a specific user
     * 
     * @param int $userId
     * @return int
     */
    public function countProjectsForUser(int $userId): int
    {
        return $this->repo->countProjectsForUser($userId);
    }

    /**
     * Ensure user has a task progress record for the project
     * 
     * @param int $userId
     * @param int $projectId
     * @return \App\Models\TaskProgress
     */
    protected function ensureTaskProgress(int $userId, int $projectId)
    {
        return TaskProgress::firstOrCreate(
            [
                'projectId' => $projectId,
                'user_id' => $userId
            ],
            [
                'progress' => 0,
                'pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD
            ]
        );
    }

    /**
     * Unpin all projects for a user
     * 
     * @param int $userId
     * @return void
     */
    protected function unpinAllProjects(int $userId): void
    {
        TaskProgress::where('user_id', $userId)
            ->update(['pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD]);
    }

    /**
     * Pin a specific project for a user
     * 
     * @param TaskProgress $taskProgress
     * @return void
     */
    protected function pinProject(TaskProgress $taskProgress): void
    {
        $taskProgress->pinned_on_dashboard = TaskProgress::PINNED_ON_DASHBOARD;
        $taskProgress->save();
    }

    /**
     * Pin a project for the current user
     * 
     * @param \App\Models\User $user
     * @param array $fields
     * @return array
     */
    public function pinProjectForUser($user, array $fields): array
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
            $taskProgress = $this->ensureTaskProgress($user->id, $fields['projectId']);
            $this->unpinAllProjects($user->id);
            $this->pinProject($taskProgress);
        });

        // Get project information
        $project = \App\Models\Project::find($fields['projectId']);
        if (!$project) {
            return [
                'error' => 'Project not found',
                'code' => 404
            ];
        }

        // Get task counts by status
        $pending = \App\Models\Task::where('projectId', $project->id)
            ->where('status', \App\Models\Task::PENDING)
            ->count();
        $completed = \App\Models\Task::where('projectId', $project->id)
            ->where('status', \App\Models\Task::COMPLETED)
            ->count();

        // Get progress
        $progress = TaskProgress::where('projectId', $project->id)
            ->where('user_id', $user->id)
            ->value('progress') ?? 0;

        return [
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'tasks' => [$pending, $completed],
                'progress' => intval($progress),
            ],
            'message' => 'Project pinned successfully'
        ];
    }

    /**
     * Calculate chart data for a project (tasks by column)
     * 
     * @param \App\Models\Project $project
     * @return array
     */
    public function calculateChartData(\App\Models\Project $project): array
    {
        $boardColumns = $project->getBoardColumns();

        // Initialize stats for each column
        $columnStats = [];
        $columnNames = [];
        $columnColors = [];

        foreach ($boardColumns as $column) {
            $position = $column['position'];
            $columnStats[$position] = 0;
            $columnNames[$position] = $column['name'];
            $columnColors[$position] = $column['color'];
        }

        // Count tasks by status
        $tasks = \App\Models\Task::where('projectId', $project->id)->get();
        foreach ($tasks as $task) {
            $status = $task->status;

            // Special handling for completed status
            if ($status === \App\Models\Task::COMPLETED) {
                // Find "Completed" column in board_columns
                foreach ($boardColumns as $column) {
                    if ($column['name'] === 'Completed') {
                        $position = $column['position'];
                        if (isset($columnStats[$position])) {
                            $columnStats[$position]++;
                        }
                        break;
                    }
                }
            } else {
                // Handle other statuses (position-based)
                $statusInt = intval($status);
                if (isset($columnStats[$statusInt])) {
                    $columnStats[$statusInt]++;
                }
            }
        }

        // Sort by column position
        ksort($columnStats);
        ksort($columnNames);
        ksort($columnColors);

        return [
            'tasks' => array_values($columnStats),
            'columnNames' => array_values($columnNames),
            'columnColors' => array_values($columnColors),
        ];
    }

    /**
     * Get the pinned project for the current user
     * 
     * @param \App\Models\User $user
     * @return array
     */
    public function getPinnedProjectForUser($user): array
    {
        $project = DB::table('task_progress')
            ->join('projects', 'task_progress.projectId', '=', 'projects.id')
            ->select('projects.id', 'projects.name')
            ->where('task_progress.pinned_on_dashboard', TaskProgress::PINNED_ON_DASHBOARD)
            ->where('task_progress.user_id', $user->id)
            ->first();

        if (!$project) {
            return [
                'data' => null,
                'message' => 'No pinned project found'
            ];
        }

        // Get project model for column information
        $projectModel = \App\Models\Project::find($project->id);
        
        // Calculate chart data
        $chartData = $this->calculateChartData($projectModel);

        // Get progress
        $progress = TaskProgress::where('projectId', $project->id)
            ->where('user_id', $user->id)
            ->value('progress') ?? 0;

        return [
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'tasks' => $chartData['tasks'],
                'columnNames' => $chartData['columnNames'],
                'columnColors' => $chartData['columnColors'],
                'progress' => intval($progress),
            ],
            'message' => 'Get pinned project successfully'
        ];
    }

    /**
     * Broadcast project creation events
     * 
     * @param \App\Models\Project $project
     * @param array $allMembers
     * @return void
     */
    private function broadcastProjectEvents($project, array $allMembers): void
    {
        // Dispatch UserProjectCountUpdated event for all members
        foreach ($allMembers as $memberId) {
            $memberProjectCount = $this->countProjectsForUser($memberId);
            UserProjectCountUpdated::dispatch($memberId, $memberProjectCount);
        }

        $project->load([
            'creator',
            'users' => function ($q) {
                $q->select('users.id', 'users.name', 'users.avatar');
            }
        ]);

        foreach ($allMembers as $memberId) {
            broadcast(new NewProjectForMembers($project, $memberId));

            // Send notification for new members
            $member = \App\Models\User::find($memberId);
            if ($member) {
                $member->notify(new \App\Notifications\NewProjectAssigned($project, $memberId));
            }
        }
    }

    /**
     * Broadcast project update events
     * 
     * @param \App\Models\Project $project
     * @param array $removedMembers
     * @param array $allMembers
     * @param array $newMembers
     * @return void
     */
    private function broadcastProjectUpdateEvents($project, array $removedMembers, array $allMembers, array $newMembers): void
    {
        $project->load([
            'creator',
            'users' => function ($q) {
                $q->select('users.id', 'users.name', 'users.avatar');
            }
        ]);

        // Dispatch UserProjectCountUpdated event for new members
        foreach ($newMembers as $memberId) {
            $memberProjectCount = $this->countProjectsForUser($memberId);
            UserProjectCountUpdated::dispatch($memberId, $memberProjectCount);
        }

        // Broadcast removal events
        foreach ($removedMembers as $removedId) {
            broadcast(new \App\Events\UserRemovedFromProject($project, $removedId));
        }

        // Broadcast update events for all members
        foreach ($allMembers as $memberId) {
            broadcast(new NewProjectForMembers($project, $memberId));
        }

        // Send notifications only to new members (not creator)
        foreach ($newMembers as $memberId) {
            if ($memberId == $project->creator_id) continue;
            $member = \App\Models\User::find($memberId);
            if ($member) {
                $member->notify(new \App\Notifications\NewProjectAssigned($project, $memberId));
            }
        }
    }
}
