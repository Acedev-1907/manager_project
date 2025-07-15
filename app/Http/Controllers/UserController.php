<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Services\GoogleDriveService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
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
        return response()->json([
            'success' => true,
            'data' => $user->only([
                'id',
                'name',
                'email',
                'phone',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at'
            ])
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
                'email_verified_at',
                'updated_at'
            ])
        ]);
    }

    /**
     * Upload user avatar to Google Drive
     */
    public function uploadAvatar(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'avatar' => 'required|file|mimes:jpg,jpeg,png|max:20480'
        ]);
        $file = $request->file('avatar');
        if ($file->getSize() > 2 * 1024 * 1024) {
            return response()->json([
                'message' => 'File too large. Please compress image to under 2MB before uploading.'
            ], 422);
        }
        try {
            $user = $request->user();
            $result = $driveService->upload($file, $user->id);
            $user->avatar = $result['link'];
            $user->save();
            return response()->json([
                'message' => 'Upload successful!',
                'file_id' => $result['file_id'],
                'link' => $result['link']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
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
}
