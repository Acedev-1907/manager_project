<?php

namespace App\Repositories;

use App\Models\Member;
use App\Models\User;

class MemberRepository
{
    public function getContacts($userId, $query = null)
    {
        $contacts = Member::with('member')
            ->where('user_id', $userId)
            ->where('member_id', '!=', $userId);
        if (!empty($query)) {
            $contacts = $contacts->whereHas('member', function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                    ->orWhere('email', 'like', "%$query%");
            });
        }
        return $contacts->paginate(6);
    }

    public function exists($userId, $memberId)
    {
        return Member::where('user_id', $userId)
            ->where('member_id', $memberId)
            ->exists();
    }

    public function create($userId, $memberId)
    {
        return Member::create([
            'user_id' => $userId,
            'member_id' => $memberId,
        ]);
    }

    public function delete($id, $userId)
    {
        $member = Member::where('id', $id)->where('user_id', $userId)->first();
        if ($member) {
            $member->delete();
            return true;
        }
        return false;
    }

    public function findUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public function findUserByNameOrEmail($input)
    {
        return User::where('email', $input)
            ->orWhere('name', $input)
            ->first();
    }
}
