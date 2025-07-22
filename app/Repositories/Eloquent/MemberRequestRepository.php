<?php

namespace App\Repositories\Eloquent;

use App\Models\MemberRequest;
use App\Repositories\Contracts\MemberRequestInterface;

class MemberRequestRepository extends BaseRepository implements MemberRequestInterface
{
    public function model()
    {
        return MemberRequest::class;
    }

    public function exists(int $senderId, int $receiverId)
    {
        return $this->model->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->exists();
    }

    public function getPendingReceivedRequestsByUserId(int $userId)
    {
        return $this->model->where('receiver_id', $userId)
                ->where('status', 'pending')
                ->with('sender:id,name,email')
                ->get();
    }

    public function findByIdAndReceiver(int $senderId, int $receiverId)
    {
        return $this->model->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->firstOrFail();
    }
}