<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function index(Request $req)
    {
        $query = $req->get('query');
        $projects = Project::with(['task_progress']);

        if (!is_null($query) && $query !== '') {
            $projects= Project::where('name', 'like', '%' . $query . '%')
                ->orderBy('id', 'desc')
                ->paginate(10);

            return response(['data' => $projects], 200);
        }

        return response(['data' => $projects], 200);
    }

    public function store(Request $req)
    {
        return DB::transaction(function () use ($req) {
            $fields = $req->all();

            $errs = Validator::make($fields, [
                'name' => 'required',
                'startDate' => 'required',
                'endDate' => 'required',
            ]);

            if ($errs->fails()) return response($errs->errors()->all(), 422);

            $project = Project::create([
                'name' => $fields['name'],
                'startDate' => $fields['startDate'],
                'endDate' => $fields['endDate'],
                'status' => Project::NOT_STARTED,
                'slug' => Project::createSlug($fields['name'])
            ]);

            TaskProgress::create([
                'projectId' => $project->id,
                'pinned_on_dashboard' => TaskProgress::NOT_PINNED_ON_DASHBOARD,
                'progress' => TaskProgress::INITIAL_PROJECT_PERCENCT,
            ]);
            return response(['message' => 'user created'], 200);
        });
    }

    public function update(Request $req)
    {
        $fields = $req->all();

        $errs = Validator::make($fields, [
            'id' => 'required',
            'name' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
        ]);

        if ($errs->fails()) return response($errs->errors()->all(), 422);

        Project::where('id', $fields['id'])->update([
            'name' => $fields['name'],
            'startDate' => $fields['startDate'],
            'endDate' => $fields['endDate'],
            'status' => Project::NOT_STARTED,
            'slug' => Project::createSlug($fields['name'])
        ]);

        return response(['message' => 'user updated'], 200);
    }

    public function pinnendProject(Request $req){
        $fields = $req->all();

        $errs = Validator::make($fields, [
            'projectId' => 'required',
        ]);

        if ($errs->fails()) {
            return response($errs->errors()->all(),422);
        }

        TaskProgress::where('projectId', $fields['projectId'])
        ->update([
            'pinned_on_dashboard' => TaskProgress::PINNED_ON_DASHBOARD
        ]);

        return response(['message'=>'project pinned on dashboard']);
    }
}
