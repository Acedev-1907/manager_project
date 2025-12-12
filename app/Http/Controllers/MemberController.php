<?php

namespace App\Http\Controllers;

use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\ApiController;

/**
 * Member Controller
 * 
 * Handles all member-related operations including adding,
 * removing, and retrieving members/contacts.
 */
class MemberController extends ApiController
{
    protected MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * Get all contacts/members for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $request->get('query', '');
        $perPage = min((int) $request->get('per_page', 52), 52); // Limit max 52 items per page
        $page = max((int) $request->get('page', 1), 1); // Ensure page >= 1

        try {
            $contacts = $this->memberService->getContacts($user, $query, $perPage, $page);
            return $this->respondWithData($contacts, 'Contacts retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error fetching members: ' . $e->getMessage());
            return $this->respondServerError('Failed to fetch members');
        }
    }

    /**
     * Add a member by ID
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $req)
    {
        $user = $req->user();
        $fields = $req->all();
        
        $errs = Validator::make($fields, [
            'member_id' => 'required|exists:users,id',
        ]);
        
        if ($errs->fails()) {
            return $this->respondValidationError($errs->errors()->first());
        }
        
        $result = $this->memberService->addMember($user, $fields['member_id']);
        
        if (isset($result['error'])) {
            return $this->respondValidationError($result['error']);
        }
        
        return $this->respondWithMessage($result['message']);
    }

    /**
     * Remove a member
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id, Request $request)
    {
        $user = $request->user();
        $result = $this->memberService->removeMember($user, $id);
        
        if (isset($result['error'])) {
            return $this->respondNotFound($result['error']);
        }
        
        return $this->respondDeleted($result['message']);
    }

    /**
     * Add a member by email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addByEmail(Request $request)
    {
        $user = $request->user();
        $fields = $request->all();
        
        $errs = Validator::make($fields, [
            'email' => 'required|email',
        ]);
        
        if ($errs->fails()) {
            return $this->respondValidationError($errs->errors()->first());
        }
        
        $result = $this->memberService->addByEmail($user, $fields['email']);
        
        if (isset($result['error'])) {
            return $this->respondNotFound($result['error']);
        }
        
        return $this->respondWithMessage($result['message']);
    }

    /**
     * Add a member by ID or email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addByIdOrEmail(Request $request)
    {
        $user = $request->user();
        $fields = $request->all();
        
        $errs = Validator::make($fields, [
            'input' => 'required',
        ]);
        
        if ($errs->fails()) {
            return $this->respondValidationError($errs->errors()->first());
        }
        
        $result = $this->memberService->addByIdOrEmail($user, $fields['input']);
        
        if (isset($result['error'])) {
            return $this->respondNotFound($result['error']);
        }
        
        return $this->respondWithMessage($result['message']);
    }
}
