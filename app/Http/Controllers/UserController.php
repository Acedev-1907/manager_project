<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function allUsers(Request $request)
    {
        $users = User::select('id', 'name', 'email')->get();
        return response(['data' => $users]);
    }
}
