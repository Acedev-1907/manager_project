<?php

namespace App\Services;

use App\Repositories\Eloquent\MemberRequestRepository;

class MemberRequestService
{
    public function __construct(protected MemberRequestRepository $memberRequestRepo){}

    public function exists(int $senderId, int $receiverId)
    {
        return $this->memberRequestRepo->exists($senderId, $receiverId);
    }
    public function create(array $data)
    {
        return $this->memberRequestRepo->create($data);
    }

    public function getReceivedFriendRequests(int $userId)
    {
        return $this->memberRequestRepo->getPendingReceivedRequestsByUserId($userId);
    }

    public function findByIdAndReceiver(int $senderId, int $receiverId)
    {
        return $this->memberRequestRepo->findByIdAndReceiver($senderId, $receiverId);
    }
}