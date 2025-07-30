<?php

namespace App\Http\Controllers;

use App\Services\MemberInvitationService;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;

/**
 * Member Invitation Controller
 * 
 * Handles all member invitation operations including sending,
 * receiving, accepting, declining, and canceling invitations.
 */
class MemberInvitationController extends ApiController
{
    protected MemberInvitationService $service;

    public function __construct(MemberInvitationService $service)
    {
        $this->service = $service;
    }

    /**
     * Send an invitation to a user by friend code
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $user = $request->user();
        $result = $this->service->sendInvitation($user, $request->input('friend_code'));

        if (isset($result['error'])) {
            return $this->respondWithError($result['error']);
        }

        return $this->respondCreated('Invitation sent', $result['invitation']->id);
    }

    /**
     * Get received invitations for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function received(Request $request)
    {
        $user = $request->user();
        $data = $this->service->getReceived($user);

        return $this->respondWithData($data);
    }

    /**
     * Get sent invitations by the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sent(Request $request)
    {
        $user = $request->user();
        $data = $this->service->getSent($user);

        return $this->respondWithData($data);
    }

    /**
     * Accept an invitation
     * 
     * @param Request $request
     * @param int $id Invitation ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept(Request $request, int $id)
    {
        $user = $request->user();
        $result = $this->service->accept($user, $id);

        if (isset($result['error'])) {
            return $this->respondNotFound($result['error']);
        }

        return $this->respondWithMessage($result['message']);
    }

    /**
     * Decline an invitation
     * 
     * @param Request $request
     * @param int $id Invitation ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function decline(Request $request, int $id)
    {
        $user = $request->user();
        $result = $this->service->decline($user, $id);

        if (isset($result['error'])) {
            return $this->respondNotFound($result['error']);
        }

        return $this->respondWithMessage($result['message']);
    }

    /**
     * Cancel a sent invitation
     * 
     * @param Request $request
     * @param int $id Invitation ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();
        $result = $this->service->cancel($user, $id);

        if (isset($result['error'])) {
            return $this->respondNotFound($result['error']);
        }

        return $this->respondWithMessage($result['message']);
    }
}
