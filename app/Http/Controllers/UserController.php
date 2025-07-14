<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get current authenticated user information
     */
    public function show(Request $request)
    {
        $user = $request->user();
        return response([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update current authenticated user information
     */
    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $updatedUser = $this->userService->updateCurrentUser($user, $data);
        return response([
            'success' => true,
            'message' => 'User information updated successfully.',
            'data' => [
                'id' => $updatedUser->id,
                'name' => $updatedUser->name,
                'email' => $updatedUser->email,
                'phone' => $updatedUser->phone,
                'avatar' => $updatedUser->avatar,
                'email_verified_at' => $updatedUser->email_verified_at,
                'updated_at' => $updatedUser->updated_at,
            ]
        ]);
    }
}
