<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Application\Auth\GuestAuthAction;
use App\Application\Auth\RegisterAction;
use App\Application\Auth\LoginAction;
use App\Application\Auth\SocialAuthAction;
use App\Domain\Auth\AppUserProvider;
use App\Http\Resources\Api\V1\AppUserResource;
use App\Support\Traits\ApiResponseTrait;
use App\Services\Auth\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected EmailOtpService $emailOtpService;

    public function __construct(EmailOtpService $emailOtpService)
    {
        $this->emailOtpService = $emailOtpService;
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
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
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
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        $email = $request->email;

        $existingProvider = AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if ($existingProvider) {
            $user = $existingProvider->user;
            if (!$user) {
                return $this->errorResponse("Invalid state.", 500);
            }
            if ($user->email_verified_at) {
                return $this->errorResponse("Email already registered", 409);
            }
            try {
                $this->emailOtpService->sendOtpForUser($user, $email);
            } catch (Exception $e) {
                // Cooldown/rate limit: still send user to OTP screen so they can resend there
            }
            return $this->successResponse([
                'needs_email_verification' => true,
                'email' => $email,
            ], "Please verify your email.", 200);
        }

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
            ], "Registration successful. Please verify your email.", 200);
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
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        try {
            $user = $action->execute($request->email, $request->password);
            
            if (!$user->email_verified_at) {
                $this->emailOtpService->sendOtpForUser($user);
                return $this->successResponse([
                    'needs_email_verification' => true,
                    'email' => $request->email,
                ], "Please verify your email.");
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
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        try {
            $this->emailOtpService->sendOtpByEmail($request->email);
        } catch (Exception $e) {
            // Always return generic success to avoid enumeration; OTP sent only when user exists and is unverified
        }
        return $this->successResponse(null, "If the email exists, an OTP has been sent.", 200);
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
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
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
            return $this->errorResponse("Invalid provider", 400);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
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
     * Logout and revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, "Logged out successfully");
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
