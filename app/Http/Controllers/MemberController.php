<?php

namespace App\Http\Controllers;

use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $request->get('query');
        $contacts = $this->memberService->getContacts($user, $query);
        return response(['data' => $contacts], 200);
    }

    public function store(Request $req)
    {
        $user = $req->user();
        $fields = $req->all();
        $errs = Validator::make($fields, [
            'member_id' => 'required|exists:users,id',
        ]);
        if ($errs->fails()) return response(['error' => $errs->errors()->first()], 422);
        $result = $this->memberService->addMember($user, $fields['member_id']);
        if (isset($result['error'])) {
            return response(['error' => $result['error']], 422);
        }
        return response(['message' => $result['message']], 200);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $result = $this->memberService->removeMember($user, $id);
        if (isset($result['error'])) {
            return response(['error' => $result['error']], 404);
        }
        return response(['message' => $result['message']]);
    }

    public function addByEmail(Request $request)
    {
        $user = $request->user();
        $fields = $request->all();
        $errs = Validator::make($fields, [
            'email' => 'required|email',
        ]);
        if ($errs->fails()) return response(['error' => $errs->errors()->first()], 422);
        $result = $this->memberService->addByEmail($user, $fields['email']);
        if (isset($result['error'])) {
            return response(['error' => $result['error']], 404);
        }
        return response(['message' => $result['message']], 200);
    }

    public function addByNameOrEmail(Request $request)
    {
        $user = $request->user();
        $fields = $request->all();
        $errs = Validator::make($fields, [
            'input' => 'required',
        ]);
        if ($errs->fails()) return response(['error' => $errs->errors()->first()], 422);
        $result = $this->memberService->addByNameOrEmail($user, $fields['input']);
        if (isset($result['error'])) {
            return response(['error' => $result['error']], 404);
        }
        return response(['message' => $result['message']], 200);
    }
}
