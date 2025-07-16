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
        $paginated = $contacts->paginate(6);
        // Map lại dữ liệu để mỗi phần tử có avatar, name, email, id
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
        $member = $this->model->where('user_id', $userId)->where('member_id', $member_id)->first();
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
