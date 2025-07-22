<?php

namespace App\Repositories\Contracts;

interface MemberRequestInterface
{
    public function exists(int $senderId, int $receiverId);

    public function getPendingReceivedRequestsByUserId(int $userId);

    public function findByIdAndReceiver(int $senderId, int $receiverId);
}
