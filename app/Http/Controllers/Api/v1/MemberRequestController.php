<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\RespondMemberRequest;
use App\Http\Requests\User\SendMemberRequest;
use App\Services\MemberRequestService;
use App\Services\MemberService;

class MemberRequestController extends ApiController
{
    public function __construct(protected MemberRequestService $memberRequestService,
                                protected MemberService $memberService) {}

    public function sendRequest(SendMemberRequest $request)
    {
        $senderId = auth()->id();
        $receiverId = $request->input('receiver_id');

        if ($senderId === $receiverId) {
            return response(['error' => 'You cannot send request to yourself'], 422);
        }

        $exits = $this->memberRequestService->exists($senderId, $receiverId);

        if ($exits) {
            return response(['error' => 'Already exists in your contact list'], 422);
        }

        $this->memberRequestService->create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId
        ]);

        return $this->respondWithMessage('Request sent');

    }

    public function receivedRequests()
    {
        $userId = auth()->id();
        $requests = $this->memberRequestService->getReceivedFriendRequests($userId);

        return $this->respondWithData($requests);
    }

    public function respond(RespondMemberRequest $request, $id)
    {
        $invitation = $this->memberRequestService->findByIdAndReceiver(auth()->id(), $id);
        $status = $request->input('status'); //accepted or rejected

        if (!in_array($status, ['accepted', 'rejected'])) {
            return response(['message' => 'Invalid status'], 422);
        }

        $invitation->status = $status;
        $invitation->save();

        if($status === 'accepted') {
            $this->memberService->addMemberCustome($invitation->sender_id, $invitation->receiver_id);
        }

        return $this->respondWithMessage('Request responded');
    }
}
