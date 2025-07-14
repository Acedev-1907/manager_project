<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Get all users
     */
    public function all()
    {
        return User::select('id', 'name', 'email')->get();
    }

    /**
     * Find user by ID
     */
    public function findById($id)
    {
        return User::find($id);
    }

    /**
     * Update user by ID
     */
    public function updateById($id, array $data)
    {
        $user = User::find($id);
        if ($user) {
            $user->update($data);
        }
        return $user;
    }
}
