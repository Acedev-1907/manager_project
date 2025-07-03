<?php

namespace App\Services;

use App\Repositories\MemberRepository;

class MemberService
{
    protected $memberRepo;

    public function __construct(MemberRepository $memberRepo)
    {
        $this->memberRepo = $memberRepo;
    }

    public function getContacts($user, $query = null)
    {
        return $this->memberRepo->getContacts($user->id, $query);
    }

    public function addMember($user, $memberId)
    {
        if ($user->id == $memberId) {
            return ['error' => 'You cannot add yourself to your contact list'];
        }
        if ($this->memberRepo->exists($user->id, $memberId)) {
            return ['error' => 'Already exists in your contact list'];
        }
        $this->memberRepo->create($user->id, $memberId);
        return ['message' => 'Added to contact list'];
    }

    public function removeMember($user, $id)
    {
        $deleted = $this->memberRepo->delete($id, $user->id);
        if (!$deleted) {
            return ['error' => 'Not found or no permission'];
        }
        return ['message' => 'Removed from contact list'];
    }

    public function addByEmail($user, $email)
    {
        $memberUser = $this->memberRepo->findUserByEmail($email);
        if (!$memberUser) {
            return ['error' => 'User not found with this information'];
        }
        return $this->addMember($user, $memberUser->id);
    }

    public function addByNameOrEmail($user, $input)
    {
        $memberUser = $this->memberRepo->findUserByNameOrEmail($input);
        if (!$memberUser) {
            return ['error' => 'User not found with this information'];
        }
        return $this->addMember($user, $memberUser->id);
    }
}
