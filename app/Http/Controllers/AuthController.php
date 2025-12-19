<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
    
class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $authService)
    {
        try {
            // Lấy IP address từ request
            $ipAddress = $request->ip();
            $user = $authService->register($request->validated(), $ipAddress);
            return response()->json([
                'user' => $user,
                'message' => __('validationMessages.register_success')
            ], 201);
        } catch (\Exception $e) {
            // Xử lý lỗi spam hoặc các lỗi khác
            return response()->json([
                'message' => $e->getMessage(),
            ], 429); // 429 Too Many Requests
        }
    }

    public function verifyEmailApi(Request $request, string $token)
    {
        $user = User::where('remember_token', $token)->first();
        if (!$user) {
            $redirect = $request->query('redirect');
            if ($redirect) {
                return redirect($redirect)->with('email_verify', 'invalid');
            }
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã được sử dụng.',
            ], 404);
        }

        $user->isValidEmail = User::IS_VALID_EMAIL;
        $user->friend_code = \App\Helpers\StringHelper::generateFriendCode($user->name, $user->id);
        $user->save();

        $redirect = $request->query('redirect');
        if ($redirect) {
            return redirect($redirect)->with('email_verify', 'success');
        }

        return response()->json([
            'success' => true,
            'message' => 'Xác thực email thành công.',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'isValidEmail' => (bool) $user->isValidEmail,
                'friend_code' => $user->friend_code,
            ],
        ]);
    }

    public function login(LoginRequest $request, AuthService $authService)
    {
        $result = $authService->login($request->validated());
        if (!$result) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 401);
        }
        return response()->json($result);
    }

    public function logoutUser(Request $req, AuthService $authService)
    {
        $result = $authService->logoutUser($req);
        return response($result, 200);
    }

    /**
     * Send reset password link to the logged-in user's email
     */
    public function sendResetPasswordLink(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('validationMessages.unauthenticated')
            ], 401);
        }
        // Prevent resending within 5 minutes
        $cacheKey = 'reset_password_sent_' . $user->id;
        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only request a password reset link once every 5 minutes.'
            ], 429);
        }
        // Create reset password token
        $token = app('auth.password.broker')->createToken($user);
        $resetUrl = url("/app/reset-password?token=$token&email=" . urlencode($user->email));
        // Send custom email
        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
        // Store cache to prevent resending for 5 minutes
        Cache::put($cacheKey, true, now()->addMinutes(5));
        return response()->json([
            'success' => true,
            'message' => __('validationMessages.reset_link_sent')
        ]);
    }

    /**
     * Handle reset password (from email link)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
        if ($status === Password::PASSWORD_RESET) {
            return response(['success' => true, 'message' => 'Password has been reset successfully.']);
        }
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Check if the reset password token is valid (for frontend pre-check)
     */
    public function checkResetToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);
        $credentials = $request->only('email', 'token');
        // Use Password broker to check token validity
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        $broker = app('auth.password.broker');
        if ($broker->tokenExists($user, $credentials['token'])) {
            return response()->json(['success' => true, 'message' => 'Token is valid.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Token is invalid or expired.'], 422);
        }
    }
}
