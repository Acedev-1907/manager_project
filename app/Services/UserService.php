<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get all users
     */
    public function getAllUsers()
    {
        return $this->userRepository->all();
    }

    /**
     * Get user by ID
     */
    public function getUserById($id)
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Update user by ID
     */
    public function updateUserById($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            unset($data['password_confirmation']);
        }
        return $this->userRepository->updateById($id, $data);
    }

    /**
     * Update current authenticated user
     */
    public function updateCurrentUser($user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            unset($data['password_confirmation']);
        }
        $user->update($data);
        return $user;
    }
}
