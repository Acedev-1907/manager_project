<?php

namespace App\Http\Controllers;

use App\Events\NewUserCreated;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        $fiels = $req->all();

        $errs = Validator::make($fiels, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|max:20'
        ]);

        if ($errs->fails()) return response($errs->errors()->all(), 422);

        $user = User::create([
            'email' => $fiels['email'],
            'password' => bcrypt($fiels['password']),
            'isValidEmail' => User::IS_INVALID_EMAIL,
            'remember_token' => $this->generateRamdonCode()
        ]);

        NewUserCreated::dispatch(($user));
        return response(['user' => $user, 'message' => 'user created'], 200);
    }

    public function generateRamdonCode()
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

    public function login(Request $req)
    {
        $fiels = $req->all();

        $errs = Validator::make($fiels, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($errs->fails()) return response($errs->errors()->all(), 422);

        $user = User::where('email', $fiels['email'])->first();
        if (!is_null($user)) {
            if (intval($user->isValidEmail) !== User::IS_VALID_EMAIL) {
                NewUserCreated::dispatch(($user));
                return response([
                    'message' => 'We send you an email verification!',
                    'isLoggedIn' => false
                ], 422);
            }
        }

        if (!$user || !Hash::check($fiels['password'], $user->password)) {
            return response([
                'user' => $user,
                'message' => 'email or password invalid',
                'isLoggedIn' => false
            ], 422);
        }

        $token = $user->createToken('secrettoken')->plainTextToken;
        return response([
            'user' => $user,
            'message' => 'loggedin',
            'token' => $token,
            'isLoggedIn' => true
        ], 200);
    }
}
