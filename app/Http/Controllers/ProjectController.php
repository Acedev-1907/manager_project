<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ProjectService;
use App\Http\Requests\Project\UpdateProjectRequest;

class ProjectController extends Controller
{
    protected $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    public function getProject($slug)
    {
        $project = Project::with(['tasks.task_members.user', 'task_progress', 'users'])
            ->where('projects.slug', $slug)
            ->first();

        return response(['data' => $project]);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = $request->get('query');
        $projects = Project::with(['task_progress', 'creator', 'users'])
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });

        if (!is_null($query)  && $query !== '') {
            $projects->where('name', 'like', '%' . $query . '%')
                ->orderBy('id', 'desc');

            return response(['data' => $projects->paginate(6)], 200);
        }
        return response(['data' => $projects->paginate(6)], 200);
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
        $result = $this->service->updateProject($request->all(), $user);

        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response(['message' => $result['message']], $result['status']);
    }

    public function pinnendProject(Request $req)
    {
        return DB::transaction(function () use ($req) {
            $fields = $req->all();

            $errs = Validator::make($fields, [
                'projectId' => 'required',
            ]);

            if ($errs->fails()) {
                return response($errs->errors()->all(), 422);
            }
            TaskProgress::where('pinned_on_dashboard', TaskProgress::PINNED_ON_DASHBOARD)
                ->update([
                    'pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD
                ]);

            TaskProgress::where('projectId', $fields['projectId'])
                ->update([
                    'pinned_on_dashboard' => TaskProgress::PINNED_ON_DASHBOARD
                ]);

            return response(['message' => 'project pinned on dashboard']);
        });
    }

    public function countProject()
    {
        $count = Project::count();
        return response(['count' => $count]);
    }

    public function getPinnnedProject()
    {
        $project = DB::table('task_progress')
            ->join('projects', 'task_progress.projectId', '=', 'projects.id')
            ->select('projects.id', 'projects.name')
            ->where('task_progress.pinned_on_dashboard', TaskProgress::PINNED_ON_DASHBOARD)
            ->first();

        if (!is_null($project)) {
            return response(['data' => $project]);
        }

        return response(['data' => null]);
    }

    public  function getProjectChartData(Request $req)
    {
        $projectId = $req->projectId;
        $task = Task::where('projectId', $projectId)->get();

        $taskProjess = TaskProgress::where('projectId', $projectId)->select('progress')->first();

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
                'progress' => intval($taskProjess->progress)
            ]
        );
    }

    // API: Get members of a specific project
    public function getProjectMembers($id)
    {
        $project = Project::with('users')->findOrFail($id);
        return response(['data' => $project->users], 200);
    }
}
