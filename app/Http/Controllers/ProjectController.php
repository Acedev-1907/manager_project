<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskProgress;
use Illuminate\Http\Request;
use App\Services\ProjectService;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Controllers\Api\ApiController;

/**
 * Project Controller
 * 
 * Handles all project-related operations including CRUD operations,
 * column management, and project analytics.
 */
class ProjectController extends ApiController
{
    protected ProjectService $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    /**
     * Get a specific project by slug
     * 
     * @param string $slug Project slug identifier
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProject(string $slug)
    {
        try {
            // Set execution time limit for this request
            set_time_limit(30);
            
            $project = $this->service->getProjectBySlug($slug);
            $user = request()->user();

            if (!$project) {
                return $this->setStatusCode(404)
                    ->setReturnCode(self::ERROR_NOT_FOUND)
                    ->respondWithError('Project not found');
            }

            if (!$project->users->contains('id', $user->id)) {
                return $this->setStatusCode(403)
                    ->setReturnCode(self::ERROR_FORBIDDEN)
                    ->respondWithError('You do not have permission to access this project');
            }

            return $this->respondWithData($project, 'Project retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Error fetching project: ' . $e->getMessage(), [
                'slug' => $slug,
                'user_id' => request()->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to retrieve project. Please try again later.');
        }
    }

    /**
     * Get all projects for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = $request->get('query');
        $projects = $this->service->getProjectsForUser($userId, $query);

        return $this->respondWithData($projects, 'Projects retrieved successfully');
    }

    /**
     * Create a new project
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        try {
            $projectDTO = \App\DTOs\ProjectDTO::fromArray($request->all());
            
            // Validate DTO
            if (!$projectDTO->isValid()) {
                return $this->setStatusCode(422)
                    ->setReturnCode(self::ERROR_VALIDATION)
                    ->respondWithError('Invalid project data');
            }
            
            $result = $this->service->createProjectWithDTO($projectDTO, $user);
            
        } catch (\Exception $e) {
            return $this->setStatusCode(422)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError($e->getMessage());
        }

        if (isset($result['errors'])) {
            return $this->setStatusCode($result['status'])
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError($result['errors']);
        }

        return $this->respondWithMessage($result['message']);
    }

    /**
     * Update an existing project
     * 
     * @param UpdateProjectRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Add a new column to a project
     * 
     * @param Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addColumn(Request $request, int $projectId)
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

            // Broadcast event để các user khác nhận được cột mới
            broadcast(new \App\Events\ColumnAdded($column, $projectId, $user->id));

            return $this->respondWithData($column, 'Column added successfully');
        } catch (\Exception $e) {
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to add column: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing column in a project
     * 
     * @param Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateColumn(Request $request, int $projectId)
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

    /**
     * Delete a column from a project
     * 
     * @param Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteColumn(Request $request, int $projectId)
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
            // Check if column has tasks before deletion
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
                // Broadcast event để các user khác nhận được cột đã bị xóa
                broadcast(new \App\Events\ColumnDeleted($columnId, $projectId, $user->id));

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

    /**
     * Pin a project for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pinnedProject(Request $request)
    {
        $result = $this->service->pinProjectForUser($request->user(), $request->all());

        if (isset($result['error'])) {
            return $this->respondWithError($result['error'], $result['code'] ?? 400);
        }

        return $this->respondWithData($result['data'], $result['message']);
    }

    /**
     * Get project count for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function countProject(Request $request)
    {
        $user = $request->user();
        $count = $this->service->countProjectsForUser($user->id);

        return $this->respondWithData(['count' => $count], 'Get project count successfully');
    }

    /**
     * Get the pinned project for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPinnedProject(Request $request)
    {
        $result = $this->service->getPinnedProjectForUser($request->user());

        if (isset($result['error'])) {
            return $this->respondWithError($result['error'], $result['code'] ?? 404);
        }

        return $this->respondWithData($result['data'], $result['message'] ?? 'Get pinned project successfully');
    }

    /**
     * Get chart data for project analytics
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectChartData(Request $request)
    {
        $projectId = $request->projectId;
        $project = Project::find($projectId);

        if (!$project) {
            return $this->respondNotFound('Project not found');
        }

        // Calculate chart data using service method
        $chartData = $this->service->calculateChartData($project);

        // Get progress
        $taskProgress = TaskProgress::where('projectId', $projectId)->select('progress')->first();
        $chartData['progress'] = intval($taskProgress->progress ?? 0);

        return $this->respondWithData($chartData, 'Chart data retrieved successfully');
    }

    /**
     * Get all members of a specific project
     * 
     * @param int $id Project ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectMembers(int $id)
    {
        $project = Project::with('users')->find($id);
        
        if (!$project) {
            return $this->respondNotFound('Project not found');
        }
        
        return $this->respondWithData($project->users, 'Project members retrieved successfully');
    }

    /**
     * Delete a project and all related data
     * 
     * @param int $id Project ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id, Request $request)
    {
        $user = $request->user();
        $result = $this->service->deleteProject($id, $user);

        if (isset($result['errors'])) {
            return $this->respondNotFound($result['errors'][0] ?? 'Project not found');
        }

        return $this->respondDeleted($result['message']);
    }

    /**
     * Get completed tasks for a project
     * 
     * @param Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompletedTasks(Request $request, int $projectId)
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

        $completedTasks = Task::getCompletedTasks($projectId);
        return $this->respondWithData($completedTasks, 'Completed tasks retrieved successfully');
    }
}
