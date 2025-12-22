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
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            'cover_photo',
            'email_verified_at',
            'created_at',
            'updated_at'
        ]);
        $data['friend_code'] = ($user->isValidEmail == \App\Models\User::IS_VALID_EMAIL) ? $user->friend_code : null;
        
        return $this->respondWithData($data, 'User information retrieved successfully');
    }

    /**
     * Update current authenticated user information
     * 
     * @param UpdateUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $updatedUser = $this->userService->updateCurrentUser($user, $data);
        
        $userData = $updatedUser->only([
            'id',
            'name',
            'email',
            'phone',
            'avatar',
            'cover_photo',
            'friend_code',
            'email_verified_at',
            'updated_at'
        ]);
        
        return $this->respondUpdatedWithData('User information updated successfully', $userData);
    }

    /**
     * Upload user avatar to ImageKit
     * Only allows user to upload their own avatar
     */
    public function uploadAvatar(Request $request, ImageKitService $imageKit)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->setStatusCode(401)
                    ->respondWithError('Unauthorized');
            }
            
            $file = $request->file('avatar');
            if (!$file) {
                return $this->setStatusCode(422)
                    ->respondWithError('No file provided');
            }
            
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
     * Upload user cover photo to ImageKit
     * Only allows user to upload their own cover photo
     */
    public function uploadCover(Request $request, ImageKitService $imageKit)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->setStatusCode(401)
                    ->respondWithError('Unauthorized');
            }
            
            $file = $request->file('cover_photo');
            if (!$file) {
                return $this->setStatusCode(422)
                    ->respondWithError('No file provided');
            }
            
            $result = $this->userService->uploadCover($user, $file, $imageKit);
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
     * 
     * Note: This endpoint returns raw image data, not JSON, so it doesn't use ApiController methods
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
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
            // Save file to cache
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
