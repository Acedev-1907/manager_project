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

    /**
     * Get all accepted members (friendship) of a user. Only members with accepted invitation are in this table.
     */
    public function getContacts($userId, $query = null)
    {
        $contacts = $this->model->with('member')
            ->where('user_id', $userId)
            ->where('member_id', '!=', $userId)
            ->orderByDesc('id'); // Order by newest added member first
        if (!empty($query)) {
            $contacts = $contacts->whereHas('member', function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                    ->orWhere('email', 'like', "%$query%");
            });
        }
        $paginated = $contacts->paginate(6);
        // Map data so each item has avatar, name, email, id
        $paginated->getCollection()->transform(function ($item) {
            return [
                'id' => $item->member->id ?? null,
                'name' => $item->member->name ?? '',
                'email' => $item->member->email ?? '',
                'avatar' => $item->member->avatar ?? '',
            ];
        });
        return $paginated;
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

    public function deleteMember($member_id, $userId)
    {
        $deleted = $this->model->where(function ($q) use ($userId, $member_id) {
            $q->where('user_id', $userId)->where('member_id', $member_id);
        })->orWhere(function ($q) use ($userId, $member_id) {
            $q->where('user_id', $member_id)->where('member_id', $userId);
        })->delete();
        return $deleted > 0;
    }

    public function findUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public function findUserByNameOrEmail($input)
    {
        // If input starts with # and followed by number, find by id
        if (preg_match('/^#(\\d+)$/', $input, $matches)) {
            return User::where('id', $matches[1])->first();
        }
        // Otherwise, find by email
        return User::where('email', $input)->first();
    }
}
