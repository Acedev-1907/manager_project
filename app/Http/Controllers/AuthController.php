<?php

namespace App\Http\Controllers;

use App\Events\NewUserCreated;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $authService)
    {
        $user = $authService->register($request->validated());
        return response()->json([
            'user' => $user,
            'message' => __('validationMessages.register_success')
        ], 201);
    }

    public function generateRandomCode()
    {
        $code = Str::random(10) . time();
        return $code;
    }

    public function validEmail($token)
    {
        User::where('remember_token', $token)
            ->update(['isValidEmail' => User::IS_VALID_EMAIL]);

        return redirect('/app/login');
    }

    public function login(LoginRequest $request, AuthService $authService)
    {
        $result = $authService->login($request->validated());
        if (!$result) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        return response()->json($result);
    }

    public function logoutUser(Request $req)
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_id', $req->userId)
            ->delete();

        return response(['message' => 'logout user'], 200);
    }
}
