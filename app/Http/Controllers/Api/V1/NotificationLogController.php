<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\NotificationLog;
use App\Domain\Notifications\Services\NotificationLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function __construct(
        private readonly NotificationLogService $logService,
    ) {}

    /**
     * POST /api/v1/notifications/log/{id}/opened
     * Marks a notification log entry as opened by the user.
     * Called by Flutter when user taps a notification.
     */
    public function markOpened(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $log  = NotificationLog::find($id);

        if (! $log || ($log->user_id !== null && $log->user_id !== $user->id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Notification log not found',
            ], 404);
        }

        $log->markOpened();

        return response()->json([
            'status'  => true,
            'message' => 'Notification marked as opened',
        ]);
    }
}
