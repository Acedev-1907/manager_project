<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function store(Request $req){
        $fiels =$req->all();

        $errs =Validator::make($fiels,[
            'name'=>'required',
            'startDate'=>'required',
            'endDate'=>'required',
        ]);

        if($errs->fails()) return response($errs->errors()->all(),422);

         Project::create([
            'name'=>$fiels['name'],
            'startDate' =>$fiels['startDate'],
            'endDate' =>$fiels['endDate'],
            'status' => Project::NOT_STARTED,
            'slug'=>Project::createSlug($fiels['name'])
        ]);

        return response(['message'=>'user created'], 200);
    }
}
