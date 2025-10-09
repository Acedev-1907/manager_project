<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Services\ImageKitService;

class UserController extends ApiController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get current authenticated user information
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $data = $user->only([
            'id',
            'name',
            'email',
            'phone',
            'avatar',
            'email_verified_at',
            'created_at',
            'updated_at'
        ]);
        $data['friend_code'] = ($user->isValidEmail == \App\Models\User::IS_VALID_EMAIL) ? $user->friend_code : null;
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update current authenticated user information
     */
    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $updatedUser = $this->userService->updateCurrentUser($user, $data);
        return response()->json([
            'success' => true,
            'message' => 'User information updated successfully.',
            'data' => $updatedUser->only([
                'id',
                'name',
                'email',
                'phone',
                'avatar',
                'friend_code',
                'email_verified_at',
                'updated_at'
            ])
        ]);
    }

    /**
     * Upload user avatar to ImageKit
     */
    public function uploadAvatar(Request $request, ImageKitService $imageKit)
    {
        try {
            $user = $request->user();
            $file = $request->file('avatar');
            $result = $this->userService->uploadAvatar($user, $file, $imageKit);
            return $this->respondWithData([
                'message' => 'Upload successful!',
                'file_id' => $result['file_id'],
                'link' => $result['url'],
                'thumbnail' => $result['thumbnail']
            ]);
        } catch (\Exception $e) {
            return $this->setStatusCode(422)
                ->respondWithError($e->getMessage());
        }
    }

    /**
     * Proxy Google Drive image to avoid CORS/hotlink issues
     */
    public function proxyImage(Request $request)
    {
        $url = $request->query('url');
        if (!$url) {
            return response('Missing url', 400);
        }
        $cacheKey = 'avatar_' . md5($url);
        $cachePath = storage_path('app/cache/' . $cacheKey . '.jpg');
        if (file_exists($cachePath)) {
            return response()->file($cachePath, [
                'Cache-Control' => 'public, max-age=31536000'
            ]);
        }
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->get($url, ['stream' => true]);
            $contentType = $res->getHeaderLine('Content-Type');
            $data = $res->getBody()->getContents();
            // Lưu file cache
            if (!file_exists(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0777, true);
            }
            file_put_contents($cachePath, $data);
            return response($data, 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=31536000');
        } catch (\Exception $e) {
            return response('Image not found', 404);
        }
    }

    /**
     * Get all users for autocomplete (not friends, not invited, not self)
     */
    public function all(Request $request)
    {
        $query = $request->get('query');
        $user = $request->user();
        $users = $this->userService->getAvailableForInvitation($user, $query);
        return $this->respondWithData($users);
    }
}
