<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Events\NewUserCreated;
use App\Services\SpamDetectionService;

class AuthService
{
    protected SpamDetectionService $spamDetection;

    public function __construct(SpamDetectionService $spamDetection)
    {
        $this->spamDetection = $spamDetection;
    }

    /**
     * Register a new user with spam detection
     * 
     * @param array $data
     * @param string|null $ipAddress
     * @param Request|null $request
     * @return User
     * @throws \Exception
     */
    public function register(array $data, $ipAddress = null, ?Request $request = null)
    {
        // Check spam using multiple factors (IP, email domain, fingerprint)
        if ($ipAddress) {
            $this->spamDetection->checkRegistrationSpam($ipAddress, $data['email'], $request);
        }
        
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

        if ($user->isValidEmail != User::IS_VALID_EMAIL) {
            return ['error' => 'Please verify your email before logging in.'];
        }
        $token = $user->createToken('api_token')->plainTextToken;
        return [
            'user' => [
                'id' => $user->id,
                'friend_code' => $user->friend_code,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'token' => $token,
        ];
    }

    public function logoutUser($req)
    {
        $user = $req->user();
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return ['message' => 'logout current device'];
    }
}
