<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Auth\CreateGuestUserAction;
use App\Application\Auth\LoginWithEmailAction;
use App\Application\Auth\RegisterDeviceTokenAction;
use App\Application\Auth\RegisterWithEmailAction;
use App\Application\Auth\SocialLoginAction;
use App\Domain\Auth\Enums\Platform;
use App\Domain\Auth\Enums\Provider;
use App\Domain\Auth\Services\SocialAuthProviderFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeviceTokenRequest;
use App\Http\Requests\Auth\GuestRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Resources\Auth\AppUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthProviderFactory $providerFactory
    ) {}

    public function guest(GuestRequest $request, CreateGuestUserAction $action): JsonResponse
    {
        $appUser = $action->execute($request->validated());
        $token = $appUser->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new AppUserResource($appUser),
        ], 201);
    }

    public function register(RegisterRequest $request, RegisterWithEmailAction $action): JsonResponse
    {
        $currentUser = $request->user();
        $appUser = $action->execute($currentUser, $request->validated());
        $token = $appUser->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new AppUserResource($appUser),
        ], 201);
    }

    public function login(LoginRequest $request, LoginWithEmailAction $action): JsonResponse
    {
        $appUser = $action->execute(
            $request->validated()['email'],
            $request->validated()['password']
        );
        $token = $appUser->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new AppUserResource($appUser),
        ]);
    }

    public function google(SocialLoginRequest $request, SocialAuthProviderFactory $factory): JsonResponse
    {
        $currentUser = $request->user('sanctum');
        $provider = $factory->make(Provider::Google);
        $action = new SocialLoginAction($provider);
        $appUser = $action->execute($currentUser, $request->validated()['id_token'], Provider::Google);
        $token = $appUser->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new AppUserResource($appUser),
        ]);
    }

    public function facebook(SocialLoginRequest $request, SocialAuthProviderFactory $factory): JsonResponse
    {
        $currentUser = $request->user();
        $provider = $factory->make(Provider::Facebook);
        $action = new SocialLoginAction($provider);
        $appUser = $action->execute($currentUser, $request->validated()['id_token'], Provider::Facebook);
        $token = $appUser->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new AppUserResource($appUser),
        ]);
    }

    public function apple(SocialLoginRequest $request, SocialAuthProviderFactory $factory): JsonResponse
    {
        $currentUser = $request->user();
        $provider = $factory->make(Provider::Apple);
        $action = new SocialLoginAction($provider);
        $appUser = $action->execute($currentUser, $request->validated()['id_token'], Provider::Apple);
        $token = $appUser->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new AppUserResource($appUser),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function deviceToken(DeviceTokenRequest $request, RegisterDeviceTokenAction $action): JsonResponse
    {
        $appUser = $request->user();
        $platform = Platform::from($request->validated()['platform']);
        $action->execute($appUser, $request->validated()['fcm_token'], $platform);

        return response()->json(['message' => 'Device token registered successfully']);
    }
}
