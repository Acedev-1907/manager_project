<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\ImageKitService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get all users
     */
    public function getAllUsers()
    {
        return $this->userRepository->all();
    }

    /**
     * Get user by ID
     */
    public function getUserById($id)
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Update user by ID
     */
    public function updateUserById($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            unset($data['password_confirmation']);
        }
        return $this->userRepository->updateById($id, $data);
    }

    /**
     * Update current authenticated user
     */
    public function updateCurrentUser($user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            unset($data['password_confirmation']);
        }
        $user->update($data);
        return $user;
    }

    /**
     * Upload user avatar, validate, upload to ImageKit, update user avatar field
     * @param $user
     * @param UploadedFile $file
     * @param ImageKitService $imageKit
     * @return array
     * @throws \Exception
     */
    public function uploadAvatar($user, UploadedFile $file, ImageKitService $imageKit)
    {
        // Validate file
        $validator = Validator::make(['avatar' => $file], [
            'avatar' => 'required|file|mimes:jpg,jpeg,png|max:20480'
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first('avatar'));
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \Exception('File too large. Please compress image to under 2MB before uploading.');
        }
        $folder = env('IMAGEKIT_AVATAR_FOLDER', 'app-manager-project') . '/avatars/' . $user->id;
        $result = $imageKit->upload($file, $folder);
        $user->avatar = $result['url'];
        $user->save();
        return $result;
    }

    /**
     * Upload user cover photo, validate, upload to ImageKit, update user cover_photo field
     * @param $user
     * @param UploadedFile $file
     * @param ImageKitService $imageKit
     * @return array
     * @throws \Exception
     */
    public function uploadCover($user, UploadedFile $file, ImageKitService $imageKit)
    {
        // Validate file
        $validator = Validator::make(['cover_photo' => $file], [
            'cover_photo' => 'required|file|mimes:jpg,jpeg,png|max:51200' // 50MB max for cover photos
        ]);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first('cover_photo'));
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \Exception('File too large. Please compress image to under 10MB before uploading.');
        }
        $folder = env('IMAGEKIT_AVATAR_FOLDER', 'app-manager-project') . '/covers/' . $user->id;
        $result = $imageKit->upload($file, $folder);
        $user->cover_photo = $result['url'];
        $user->save();
        return $result;
    }

    /**
     * Get users for autocomplete: status = friend, invited, available
     */
    public function getAvailableForInvitation($user, $query = null)
    {
        // Lấy id bạn bè
        $friendIds = DB::table('members')->where('user_id', $user->id)->pluck('member_id')->toArray();
        // Lấy id đã gửi lời mời
        $invitedIds = DB::table('member_invitations')
            ->where('sender_id', $user->id)
            ->where('status', 'pending')
            ->pluck('receiver_id')->toArray();
        // Lấy tất cả user phù hợp
        $users = \App\Models\User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($query, $user) {
                if ($query) {
                    $q->where(function ($q2) use ($query) {
                        $q2->where('name', 'like', "%$query%")
                            ->orWhere('friend_code', 'like', "%$query%");
                    });
                }
                // Không bao giờ trả về user chính mình
                $q->where('id', '!=', $user->id);
            })
            ->select('id', 'name', 'friend_code', 'avatar')
            ->limit(10)
            ->get();
        // Gán status
        foreach ($users as $u) {
            if (in_array($u->id, $friendIds)) {
                $u->status = 'friend';
            } elseif (in_array($u->id, $invitedIds)) {
                $u->status = 'invited';
            } else {
                $u->status = 'available';
            }
        }
        return $users;
    }
}
