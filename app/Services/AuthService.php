<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Events\NewUserCreated;

class AuthService
{
    /**
     * Kiểm tra spam tạo user
     * Nếu một IP tạo quá 5 user liên tục (trong khoảng thời gian ngắn) thì block
     */
    protected function checkSpamRegistration($ipAddress)
    {
        $blockKey = "user_registration_spam_block_{$ipAddress}";
        
        // Kiểm tra xem IP có đang bị block không
        if (Cache::has($blockKey)) {
            $blockUntil = Cache::get("user_registration_spam_block_until_{$ipAddress}");
            if ($blockUntil) {
                $remainingMinutes = max(0, Carbon::parse($blockUntil)->diffInMinutes(Carbon::now()));
                throw new \Exception("IP của bạn đã bị tạm khóa do tạo quá nhiều tài khoản liên tục. Vui lòng đợi {$remainingMinutes} phút trước khi đăng ký tiếp theo.");
            }
            throw new \Exception('IP của bạn đã bị tạm khóa do tạo quá nhiều tài khoản liên tục. Vui lòng đợi một chút trước khi đăng ký tiếp theo.');
        }
        
        $maxUsers = 4; // Kiểm tra 4 user gần nhất (user hiện tại sẽ là user thứ 5)
        $timeWindow = 10; // 10 phút - khoảng thời gian để coi là "liên tục"
        $registrationKey = "user_registration_times_{$ipAddress}";
        
        // Lấy danh sách thời điểm đăng ký từ IP này
        $registrationTimes = Cache::get($registrationKey, []);
        $now = Carbon::now();
        
        // Lọc bỏ các đăng ký cũ hơn timeWindow
        $registrationTimes = array_filter($registrationTimes, function($time) use ($now, $timeWindow) {
            return Carbon::parse($time)->diffInMinutes($now) <= $timeWindow;
        });
        
        // Nếu đã có 4 đăng ký trong khoảng thời gian ngắn -> đây sẽ là đăng ký thứ 5 -> spam
        if (count($registrationTimes) >= $maxUsers) {
            // Lưu vào cache để block IP trong một khoảng thời gian
            $blockDuration = 60; // Block 60 phút
            $blockUntilTime = now()->addMinutes($blockDuration);
            Cache::put($blockKey, true, $blockUntilTime);
            Cache::put("user_registration_spam_block_until_{$ipAddress}", $blockUntilTime->toDateTimeString(), $blockUntilTime);
            
            throw new \Exception('Bạn đã tạo quá nhiều tài khoản liên tục (5 tài khoản trong vòng 10 phút). IP của bạn đã bị tạm khóa trong 60 phút. Vui lòng đợi trước khi đăng ký tiếp theo.');
        }
        
        // Lưu thời điểm đăng ký hiện tại vào cache
        $registrationTimes[] = $now->toDateTimeString();
        Cache::put($registrationKey, $registrationTimes, now()->addMinutes($timeWindow + 1));
        
        return true;
    }

    public function register(array $data, $ipAddress = null)
    {
        // Kiểm tra spam trước khi tạo user
        // Nếu có IP từ request thì kiểm tra spam
        if ($ipAddress) {
            $this->checkSpamRegistration($ipAddress);
        }
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'isValidEmail' => 0,
            'remember_token' => Str::random(60),
        ]);
        NewUserCreated::dispatch($user);
        return $user;
    }

    public function login(array $data)
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return false;
        }
        $user = Auth::user();

        if ($user->isValidEmail != User::IS_VALID_EMAIL) {
            return ['error' => 'Please verify your email before logging in.'];
        }
        $token = $user->createToken('api_token')->plainTextToken;
        return [
            'user' => [
                'id' => $user->id,
                'friend_code' => $user->friend_code,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'token' => $token,
        ];
    }

    public function logoutUser($req)
    {
        $user = $req->user();
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return ['message' => 'logout current device'];
    }
}
