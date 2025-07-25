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
            return response(['message' => 'You do not have permission to access this project!'], 403);
        }
        return response(['data' => $project]);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = $request->get('query');
        $projects = $this->service->getProjectsForUser($userId, $query);
        return response(['data' => $projects], 200);
    }

    public function store(Request $req)
    {
        $user = $req->user();

        $result = $this->service->createProject($req->all(), $user);

        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response(['message' => $result['message']], $result['status']);
    }

    public function update(UpdateProjectRequest $request)
    {
        $user = $request->user();
        $projectId = $request->input('id');
        $project = Project::find($projectId);

        if (!$project) {
            return response(['message' => 'Project does not exist'], 404);
        }

        if ($project->creator_id !== $user->id) {
            return response(['message' => 'You cannot edit this project'], 403);
        }

        $result = $this->service->updateProject($request->all(), $user);

        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response(['message' => $result['message']], $result['status']);
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
        return response(['count' => $count]);
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
}
