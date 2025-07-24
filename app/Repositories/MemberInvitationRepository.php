<?php

namespace App\Repositories;

use App\Models\MemberInvitation;

class MemberInvitationRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return MemberInvitation::class;
    }

    public function existsPending($senderId, $receiverId)
    {
        return $this->model->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->exists();
    }

    public function getPendingReceived($userId)
    {
        return $this->model->with(['sender' => function ($q) {
            $q->select('id', 'name', 'email', 'avatar');
        }])
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->get();
    }

    public function getPendingSent($userId)
    {
        return $this->model->with('receiver')
            ->where('sender_id', $userId)
            ->where('status', 'pending')
            ->get();
    }

    public function findPendingByIdAndReceiver($id, $receiverId)
    {
        return $this->model->where('id', $id)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->first();
    }

    public function findPendingByIdAndSender($id, $senderId)
    {
        return $this->model->where('id', $id)
            ->where('sender_id', $senderId)
            ->where('status', 'pending')
            ->first();
    }
}
