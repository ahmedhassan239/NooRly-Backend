<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Application\Auth\GuestAuthAction;
use App\Application\Auth\RegisterAction;
use App\Application\Auth\LoginAction;
use App\Application\Auth\SocialAuthAction;
use App\Http\Resources\Api\V1\AppUserResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    use ApiResponseTrait;

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
     */
    public function register(Request $request, RegisterAction $action)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:male,female,other,unknown',
            'birth_date' => 'nullable|date',
            'locale' => 'nullable|string|in:en,ar',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        try {
            $user = $action->execute($request->all());
            $token = $user->createToken('auth_token')->plainTextToken;
            return $this->authResponse($user, $token);
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
            $token = $user->createToken('auth_token')->plainTextToken;
            return $this->authResponse($user, $token);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
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
