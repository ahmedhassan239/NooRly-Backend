<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\UserNotificationToken;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registers device tokens for future FCM/APNs. Safe no-op until push driver is enabled.
 */
class NotificationDeviceTokenController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
            'provider' => ['nullable', 'string', 'max:32'],
        ]);

        $provider = $validated['provider'] ?? config('noorly.push.driver', 'null');

        UserNotificationToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'],
                'token' => $validated['token'],
            ],
            [
                'provider' => $provider === 'null' ? null : $provider,
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );

        return $this->successResponse(null, 'Token registered');
    }
}
