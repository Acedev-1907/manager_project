<?php

namespace App\Repositories;

use App\Models\Member;
use App\Models\User;

class MemberRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Member::class;
    }

    public function getContacts($userId, $query = null)
    {
        $contacts = $this->model->with('member')
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
        return $this->model->where('user_id', $userId)
            ->where('member_id', $memberId)
            ->exists();
    }

    public function createMember($userId, $memberId)
    {
        return $this->model->create([
            'user_id' => $userId,
            'member_id' => $memberId,
        ]);
    }

    public function deleteMember($id, $userId)
    {
        $member = $this->model->where('id', $id)->where('user_id', $userId)->first();
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
