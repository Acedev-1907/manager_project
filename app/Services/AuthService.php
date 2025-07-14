<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Events\NewUserCreated;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'isValidEmail' => 0,
            'remember_token' => Str::random(60),
        ]);
        NewUserCreated::dispatch($user);
        return $user;
    }

    public function login(array $data)
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return false;
        }
        $user = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;
        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar, // add avatar
                // 'phone' => $user->phone, // optionally add phone
            ],
            'token' => $token,
        ];
    }

    public function logoutUser($userId)
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->delete();
        return ['message' => 'logout user'];
    }
}
