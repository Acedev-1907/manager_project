<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskProgress;
use Illuminate\Http\Request;
use App\Services\ProjectService;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Controllers\Api\ApiController;

class ProjectController extends ApiController
{
    protected $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function getProject($slug)
    {
        $project = $this->service->getProjectBySlug($slug);
        $user = request()->user();

        if (!$project || !$project->users->contains('id', $user->id)) {
            return $this->setStatusCode(403)
                ->setReturnCode(self::ERROR_FORBIDDEN)
                ->respondWithError('You do not have permission to access this project');
        }
        return $this->respondWithData($project, 'Project retrieved successfully');
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = $request->get('query');
        $projects = $this->service->getProjectsForUser($userId, $query);



        return $this->respondWithData($projects, 'Projects retrieved successfully');
    }

    public function store(Request $req)
    {
        $user = $req->user();

        $result = $this->service->createProject($req->all(), $user);

        if (isset($result['errors'])) {
            return $this->setStatusCode($result['status'])
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError($result['errors']);
        }
        return $this->respondWithMessage($result['message']);
    }

    public function update(UpdateProjectRequest $request)
    {
        $user = $request->user();
        $projectId = $request->input('id');
        $project = Project::find($projectId);

        if (!$project) {
            return $this->respondNotFound('Project does not exist');
        }

        if ($project->creator_id !== $user->id) {
            return $this->setStatusCode(403)
                ->setReturnCode(self::ERROR_FORBIDDEN)
                ->respondWithError('You cannot edit this project');
        }

        $result = $this->service->updateProject($request->all(), $user);

        if (isset($result['errors'])) {
            return $this->setStatusCode($result['status'])
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError($result['errors']);
        }
        return $this->respondUpdated($result['message']);
    }

    public function addColumn(Request $request, $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->respondNotFound('Project not found');
        }

        if (!$project->users->contains('id', $user->id)) {
            return $this->setStatusCode(403)
                ->setReturnCode(self::ERROR_FORBIDDEN)
                ->respondWithError('You do not have permission to access this project');
        }

        $columnData = $request->input('add_column');
        if (!$columnData) {
            return $this->setStatusCode(400)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError('Column data is required');
        }

        try {
            $column = $project->addColumn(
                $columnData['name'],
                $columnData['color'] ?? '#3b82f6',
                $columnData['icon'] ?? 'fas fa-columns'
            );

            return $this->respondWithData($column, 'Column added successfully');
        } catch (\Exception $e) {
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to add column: ' . $e->getMessage());
        }
    }

    public function updateColumn(Request $request, $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->respondNotFound('Project not found');
        }

        if (!$project->users->contains('id', $user->id)) {
            return $this->setStatusCode(403)
                ->setReturnCode(self::ERROR_FORBIDDEN)
                ->respondWithError('You do not have permission to access this project');
        }

        $columnData = $request->input('update_column');
        if (!$columnData || !isset($columnData['column_id'])) {
            return $this->setStatusCode(400)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError('Column ID and data are required');
        }

        try {
            $updatedColumn = $project->updateColumn($columnData['column_id'], [
                'name' => $columnData['name'],
                'color' => $columnData['color'] ?? '#3b82f6',
                'icon' => $columnData['icon'] ?? 'fas fa-columns'
            ]);

            if ($updatedColumn) {
                return $this->respondWithData($updatedColumn, 'Column updated successfully');
            } else {
                return $this->respondNotFound('Column not found');
            }
        } catch (\Exception $e) {
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to update column: ' . $e->getMessage());
        }
    }

    public function deleteColumn(Request $request, $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->respondNotFound('Project not found');
        }

        if (!$project->users->contains('id', $user->id)) {
            return $this->setStatusCode(403)
                ->setReturnCode(self::ERROR_FORBIDDEN)
                ->respondWithError('You do not have permission to access this project');
        }

        $columnId = $request->input('column_id');
        if (!$columnId) {
            return $this->setStatusCode(400)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError('Column ID is required');
        }

        try {
            // Check if column has tasks
            $columnStatus = $project->getColumnStatus($columnId);
            if ($columnStatus !== null) {
                $tasksInColumn = $project->tasks()->where('status', $columnStatus)->count();
                if ($tasksInColumn > 0) {
                    return $this->setStatusCode(400)
                        ->setReturnCode(self::ERROR_VALIDATION)
                        ->respondWithError("Cannot delete column. It contains {$tasksInColumn} task(s). Please move or delete all tasks first.");
                }
            }

            $deleted = $project->deleteColumn($columnId);
            if ($deleted) {
                return $this->respondWithMessage('Column deleted successfully');
            } else {
                return $this->respondNotFound('Column not found');
            }
        } catch (\Exception $e) {
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to delete column: ' . $e->getMessage());
        }
    }

    public function pinnedProject(Request $request)
    {
        $result = $this->service->pinProjectForUser($request->user(), $request->all());
        if (isset($result['error'])) {
            return $this->respondWithError($result['error'], $result['code'] ?? 400);
        }
        return $this->respondWithData($result['data'], $result['message']);
    }

    public function countProject(Request $request)
    {
        $user = $request->user();
        $count = $this->service->countProjectsForUser($user->id);
        return $this->respondWithData(['count' => $count], 'Get project count successfully');
    }

    public function getPinnedProject(Request $request)
    {
        $result = $this->service->getPinnedProjectForUser($request->user());
        if (isset($result['error'])) {
            return $this->respondWithError($result['error'], $result['code'] ?? 404);
        }
        return $this->respondWithData($result['data'], $result['message'] ?? 'Get pinned project successfully');
    }

    public  function getProjectChartData(Request $req)
    {
        $projectId = $req->projectId;
        $task = Task::where('projectId', $projectId)->get();

        $taskProject = TaskProgress::where('projectId', $projectId)->select('progress')->first();

        $pending = 0;
        $completed = 0;
        foreach ($task as $row) {
            if (intval($row->status) === Task::PENDING) {
                $pending++;
            }

            if (intval($row->status) === Task::COMPLETED) {
                $completed++;
            }
        }

        return response(
            [
                'tasks' => [$pending, $completed],
                'progress' => intval($taskProject->progress)
            ]
        );
    }

    // API: Get members of a specific project
    public function getProjectMembers($id)
    {
        $project = Project::with('users')->findOrFail($id);
        return response(['data' => $project->users], 200);
    }

    /**
     * Delete a project if the user is the creator. Also deletes all related tasks, members, and related data.
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $result = $this->service->deleteProject($id, $user);
        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response(['message' => $result['message']], $result['status']);
    }

    /**
     * Get completed tasks for a project
     */
    public function getCompletedTasks(Request $request, $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->respondNotFound('Project not found');
        }

        if (!$project->users->contains('id', $user->id)) {
            return $this->setStatusCode(403)
                ->setReturnCode(self::ERROR_FORBIDDEN)
                ->respondWithError('You do not have permission to access this project');
        }

        $completedTasks = \App\Models\Task::getCompletedTasks($projectId);
        return $this->respondWithData($completedTasks, 'Completed tasks retrieved successfully');
    }
}
