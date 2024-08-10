<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\TaskProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MemberContrller extends Controller
{
    public function index(Request $req)
    {
        $query = $req->get('query');
        $members = DB::table('members');

        if (!is_null($query) && $query !== '') {
            $members->where('name', 'like', '%' . $query . '%')
                ->orderBy('id', 'desc');

            return response(['data' => $members->paginate(10)], 200);
        }

        return response(['data' => $members->paginate(10)], 200);
    }

    public function store(Request $req)
    {
        return DB::transaction(function () use ($req) {
            $fields = $req->all();

            $errs = Validator::make($fields, [
                'name' => 'required',
                'email' => 'required|email',
            ]);

            if ($errs->fails()) return response($errs->errors()->all(), 422);

            $member = Member::create([
                'name' => $fields['name'],
                'email' => $fields['email'],
            ]);

            return response(['message' => 'user created'], 200);
        });
    }

    public function update(Request $req)
    {
        $fields = $req->all();

        $errs = Validator::make($fields, [
            'id' => 'required|numeric',
            'name' => 'required',
            'email' => 'required',
        ]);

        if ($errs->fails()) return response($errs->errors()->all(), 422);

        Member::where('id', $fields['id'])->update([
            'name' => $fields['name'],
            'email' => $fields['email'],
        ]);

        return response(['message' => 'member updated'], 200);
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
