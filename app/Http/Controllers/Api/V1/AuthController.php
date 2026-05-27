<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Auth\GuestAuthAction;
use App\Application\Auth\LoginAction;
use App\Application\Auth\RegisterAction;
use App\Application\Auth\SocialAuthAction;
use App\Domain\Auth\AppUserProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppUserResource;
use App\Services\Auth\EmailOtpService;
use App\Services\Auth\PasswordResetService;
use App\Support\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected EmailOtpService $emailOtpService;

    protected PasswordResetService $passwordResetService;

    public function __construct(EmailOtpService $emailOtpService, PasswordResetService $passwordResetService)
    {
        $this->emailOtpService = $emailOtpService;
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Authenticate or create a guest user.
     */
    public function guest(Request $request, GuestAuthAction $action)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'locale' => 'nullable|string|in:en,ar',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $user = $action->execute($request->device_id, $request->get('locale', 'en'));
        $token = $user->createToken('guest_token')->plainTextToken;

        return $this->authResponse($user, $token);
    }

    /**
     * Register a new user.
     * Never issues a token; returns needs_email_verification until OTP is verified.
     * If email exists but is not verified: resend OTP and return needs_email_verification (200).
     * If email exists and is verified: return 409 "Email already registered".
     */
    public function register(Request $request, RegisterAction $action)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:male,female',
            'birth_date' => 'nullable|date|before:today',
            'locale' => 'nullable|string|in:en,ar',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $email = $request->email;

        // ─── 1. Check for a SOFT-DELETED account with this email ──────────────
        // The provider table has no soft-delete; the user (AppUser) does.
        // We use withTrashed() on the user relation side via a join.
        $deletedProvider = AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->whereHas('userWithTrashed', fn ($q) => $q->whereNotNull('deleted_at'))
            ->first();

        if ($deletedProvider) {
            // Restore the account and update credentials
            $user = $action->restoreDeleted($deletedProvider, $request->all());

            try {
                $this->emailOtpService->sendOtpForUser($user, $email);
            } catch (Exception $e) {
                // Cooldown/rate-limit: user will see OTP screen and can resend
            }

            return $this->successResponse([
                'needs_email_verification' => true,
                'email' => $email,
                'account_restored' => true,
            ], 'Your account has been restored. Please verify your email.', 200);
        }

        // ─── 2. Check for an existing ACTIVE account ──────────────────────────
        $existingProvider = AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if ($existingProvider) {
            $user = $existingProvider->user;
            if (! $user) {
                return $this->errorResponse('Invalid state.', 500);
            }
            if ($user->email_verified_at) {
                return $this->errorResponse('Email already registered', 409);
            }
            try {
                $this->emailOtpService->sendOtpForUser($user, $email);
            } catch (Exception $e) {
                // Cooldown/rate limit: still send user to OTP screen so they can resend there
            }

            return $this->successResponse([
                'needs_email_verification' => true,
                'email' => $email,
            ], 'Please verify your email.', 200);
        }

        // ─── 3. Brand-new registration ────────────────────────────────────────
        try {
            $user = $action->execute($request->all());
            try {
                $this->emailOtpService->sendOtpForUser($user, $email);
            } catch (Exception $e) {
                // Cooldown/rate limit: still return success so user can go to OTP screen
            }

            return $this->successResponse([
                'needs_email_verification' => true,
                'email' => $email,
            ], 'Registration successful. Please verify your email.', 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Login with email and password.
     */
    public function login(Request $request, LoginAction $action)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $user = $action->execute($request->email, $request->password);

            if (! $user->email_verified_at) {
                $this->emailOtpService->sendOtpForUser($user);

                return $this->successResponse([
                    'needs_email_verification' => true,
                    'email' => $request->email,
                ], 'Please verify your email.');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->authResponse($user, $token);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Send OTP to email.
     */
    public function sendEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $this->emailOtpService->sendOtpByEmail($request->email);
        } catch (Exception $e) {
            // Preserve generic response but log operational failures for debugging.
            Log::warning('Email OTP send failed', [
                'email' => strtolower(trim((string) $request->email)),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return $this->successResponse(null, 'If the email exists, an OTP has been sent.', 200);
    }

    /**
     * Verify OTP.
     */
    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $user = $this->emailOtpService->verifyOtp($request->email, $request->otp);
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->authResponse($user, $token);
        } catch (Exception $e) {
            $code = $e->getCode() === 429 ? 429 : 422;

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * Authenticate via social provider.
     */
    public function social(Request $request, string $provider, SocialAuthAction $action)
    {
        $rules = [];
        $token = null;

        if ($provider === 'google') {
            $rules['id_token'] = 'required|string';
            $token = $request->id_token;
        } elseif ($provider === 'facebook') {
            $rules['access_token'] = 'required|string';
            $token = $request->access_token;
        } elseif ($provider === 'apple') {
            $rules['identity_token'] = 'required|string';
            $token = $request->identity_token;
        } else {
            return $this->errorResponse('Invalid provider', 400);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $user = $action->execute($provider, $token, $request->all());
            $tokenResult = $user->createToken('social_token')->plainTextToken;

            return $this->authResponse($user, $tokenResult);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Request password-reset OTP.
     * Same success response whether email exists or not.
     */
    public function requestForgotPasswordOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $this->passwordResetService->requestOtp($request->email);
        } catch (Exception $e) {
            if ($e->getCode() === 429) {
                return $this->errorResponse($e->getMessage(), 429);
            }

            // Important: don't swallow infra/runtime failures silently.
            Log::error('Forgot-password OTP request failed', [
                'email' => strtolower(trim((string) $request->email)),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return $this->errorResponse('Unable to send verification code right now. Please try again shortly.', 500);
        }

        return $this->successResponse(
            null,
            'If an account exists, we sent a verification code.',
            200
        );
    }

    /**
     * Verify password-reset OTP and issue short-lived reset token.
     */
    public function verifyForgotPasswordOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $data = $this->passwordResetService->verifyOtp(
                $request->email,
                $request->otp
            );

            return $this->successResponse($data, 'Verification code accepted.', 200);
        } catch (Exception $e) {
            $code = $e->getCode() === 429 ? 429 : 422;

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * Reset password with verified reset token.
     */
    public function resetForgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $this->passwordResetService->resetWithVerifiedToken(
                $request->email,
                $request->reset_token,
                $request->password
            );

            return $this->successResponse(null, 'Password has been reset.', 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Generate a consistent auth response.
     */
    protected function authResponse($user, $token)
    {
        $user->load(['profile', 'providers']);

        $data = [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AppUserResource($user),
        ];

        return $this->successResponse($data, 'Authenticated successfully');
    }
}
