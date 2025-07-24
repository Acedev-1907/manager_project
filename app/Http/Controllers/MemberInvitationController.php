<?php

namespace App\Http\Controllers;

use App\Services\MemberInvitationService;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;

class MemberInvitationController extends ApiController
{
    protected $service;
    public function __construct(MemberInvitationService $service)
    {
        $this->service = $service;
    }

    public function send(Request $request)
    {
        $user = $request->user();
        $result = $this->service->sendInvitation($user, $request->input('friend_code'));
        if (isset($result['error'])) return $this->respondWithError($result['error']);
        return $this->respondCreated('Invitation sent', $result['invitation']->id);
    }

    public function received(Request $request)
    {
        $user = $request->user();
        $data = $this->service->getReceived($user);
        return $this->respondWithData($data);
    }

    public function sent(Request $request)
    {
        $user = $request->user();
        $data = $this->service->getSent($user);
        return $this->respondWithData($data);
    }

    public function accept(Request $request, $id)
    {
        $user = $request->user();
        $result = $this->service->accept($user, $id);
        if (isset($result['error'])) return $this->respondNotFound($result['error']);
        return $this->respondWithMessage($result['message']);
    }

    public function decline(Request $request, $id)
    {
        $user = $request->user();
        $result = $this->service->decline($user, $id);
        if (isset($result['error'])) return $this->respondNotFound($result['error']);
        return $this->respondWithMessage($result['message']);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $result = $this->service->cancel($user, $id);
        if (isset($result['error'])) return $this->respondNotFound($result['error']);
        return $this->respondWithMessage($result['message']);
    }
}
