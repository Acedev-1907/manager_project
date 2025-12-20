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
use App\Http\Controllers\Api\ApiController;
    
class AuthController extends ApiController
{
    /**
     * Register a new user
     * 
     * @param RegisterRequest $request
     * @param AuthService $authService
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request, AuthService $authService)
    {
        try {
            // Get IP address from request
            $ipAddress = $request->ip();
            $user = $authService->register($request->validated(), $ipAddress, $request);
            return $this->setStatusCode(201)
                ->setReturnCode(self::RESPONSE_CREATED)
                ->respondWithData(['user' => $user], __('validationMessages.register_success'));
        } catch (\Exception $e) {
            // Handle spam or other errors
            return $this->setStatusCode(429)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError($e->getMessage());
        }
    }

    /**
     * Verify user email with token
     * 
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function verifyEmailApi(Request $request, string $token)
    {
        $user = User::where('remember_token', $token)->first();
        if (!$user) {
            $redirect = $request->query('redirect');
            if ($redirect) {
                return redirect($redirect)->with('email_verify', 'invalid');
            }
            return $this->respondNotFound('Invalid or expired token');
        }

        $user->isValidEmail = User::IS_VALID_EMAIL;
        $user->friend_code = \App\Helpers\StringHelper::generateFriendCode($user->name, $user->id);
        $user->save();

        $redirect = $request->query('redirect');
        if ($redirect) {
            return redirect($redirect)->with('email_verify', 'success');
        }

        return $this->respondWithData([
            'id' => $user->id,
            'email' => $user->email,
            'isValidEmail' => (bool) $user->isValidEmail,
            'friend_code' => $user->friend_code,
        ], 'Email verified successfully');
    }

    /**
     * Login user
     * 
     * Note: Login endpoint returns data directly (not wrapped in ApiController format)
     * to maintain compatibility with frontend authentication flow
     * 
     * @param LoginRequest $request
     * @param AuthService $authService
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, AuthService $authService)
    {
        $result = $authService->login($request->validated());
        if (!$result) {
            return $this->setStatusCode(401)
                ->setReturnCode(self::ERROR_UNAUTHORIZED)
                ->respondWithError('Invalid credentials');
        }
        if (isset($result['error'])) {
            return $this->setStatusCode(401)
                ->setReturnCode(self::ERROR_UNAUTHORIZED)
                ->respondWithError($result['error']);
        }
        
        // Return login data directly (user + token) for frontend compatibility
        // Frontend expects: { user: {...}, token: "..." }
        return response()->json($result, 200);
    }

    /**
     * Logout user
     * 
     * @param Request $req
     * @param AuthService $authService
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutUser(Request $req, AuthService $authService)
    {
        $result = $authService->logoutUser($req);
        return $this->respondWithMessage('Logout successful');
    }

    /**
     * Send reset password link to the logged-in user's email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetPasswordLink(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->setStatusCode(401)
                ->setReturnCode(self::ERROR_UNAUTHORIZED)
                ->respondWithError(__('validationMessages.unauthenticated'));
        }
        // Prevent resending within 5 minutes
        $cacheKey = 'reset_password_sent_' . $user->id;
        if (Cache::has($cacheKey)) {
            return $this->setStatusCode(429)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError('You can only request a password reset link once every 5 minutes.');
        }
        // Create reset password token
        $token = app('auth.password.broker')->createToken($user);
        $resetUrl = url("/app/reset-password?token=$token&email=" . urlencode($user->email));
        // Send custom email
        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
        // Store cache to prevent resending for 5 minutes
        Cache::put($cacheKey, true, now()->addMinutes(5));
        return $this->respondWithMessage(__('validationMessages.reset_link_sent'));
    }

    /**
     * Handle reset password (from email link)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            return $this->respondUpdated('Password has been reset successfully');
        }
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Check if the reset password token is valid (for frontend pre-check)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            return $this->respondNotFound('User not found');
        }
        $broker = app('auth.password.broker');
        if ($broker->tokenExists($user, $credentials['token'])) {
            return $this->respondWithMessage('Token is valid');
        } else {
            return $this->respondValidationError('Token is invalid or expired');
        }
    }
}
