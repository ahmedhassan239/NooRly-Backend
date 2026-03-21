<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\Campaigns\NotificationCampaignDelivery;
use App\Domain\Notifications\Campaigns\NotificationInbox;
use App\Domain\Notifications\Campaigns\NotificationLocalizedContentResolver;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * App-pull delivery pipeline for admin campaigns (not background push).
 */
class UserPendingNotificationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly NotificationLocalizedContentResolver $localizedContentResolver,
    ) {}

    /**
     * GET /api/v1/user/pending-notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('settings');

        $deliveries = NotificationCampaignDelivery::query()
            ->where('user_id', $user->id)
            ->where('delivery_status', 'pending_for_app_pull')
            ->with('campaign')
            ->orderBy('id')
            ->get();

        $items = $deliveries->map(function (NotificationCampaignDelivery $d) use ($user) {
            $campaign = $d->campaign;
            if (! $campaign) {
                return null;
            }

            $payload = $this->localizedContentResolver->resolveForUser(
                $user,
                $campaign->title_ar,
                $campaign->title_en,
                $campaign->body_ar,
                $campaign->body_en,
            );

            return [
                'id' => $d->id,
                'campaign_id' => $campaign->id,
                'type' => $campaign->type,
                'title' => $payload->title,
                'body' => $payload->body,
                'locale' => $payload->locale,
                'title_ar' => $campaign->title_ar,
                'title_en' => $campaign->title_en,
                'body_ar' => $campaign->body_ar,
                'body_en' => $campaign->body_en,
                'route' => $campaign->route,
                'priority' => $campaign->priority,
                'created_at' => $d->created_at?->toIso8601String(),
            ];
        })->filter()->values()->all();

        Log::info('user.pending_notifications.index', [
            'user_id' => $user->id,
            'count' => count($items),
        ]);

        return $this->successResponse([
            'items' => $items,
            'count' => count($items),
        ]);
    }

    /**
     * POST /api/v1/user/pending-notifications/{id}/mark-shown
     */
    public function markShown(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $delivery = NotificationCampaignDelivery::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->firstOrFail();

        if ($delivery->delivery_status === 'pending_for_app_pull') {
            $delivery->update([
                'delivery_status' => 'shown_locally',
                'shown_locally_at' => now(),
            ]);
        }

        $delivery->refresh();

        Log::info('user.pending_notifications.mark_shown', [
            'user_id' => $user->id,
            'delivery_id' => (int) $id,
            'status' => $delivery->delivery_status,
        ]);

        return $this->successResponse([
            'id' => (int) $id,
            'delivery_status' => $delivery->delivery_status,
        ]);
    }

    /**
     * POST /api/v1/user/pending-notifications/{id}/mark-read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $delivery = NotificationCampaignDelivery::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->firstOrFail();

        if (in_array($delivery->delivery_status, ['pending_for_app_pull', 'shown_locally'], true)) {
            $delivery->update([
                'delivery_status' => 'read',
                'read_at' => now(),
                'opened_at' => $delivery->opened_at ?? now(),
            ]);
        } elseif ($delivery->delivery_status !== 'read') {
            // Idempotent for already-read; other states left unchanged
            if ($delivery->delivery_status === 'sent') {
                $delivery->update([
                    'opened_at' => $delivery->opened_at ?? now(),
                ]);
            }
        }

        NotificationInbox::query()
            ->where('delivery_id', $delivery->id)
            ->update(['is_read' => true]);

        $delivery->refresh();

        Log::info('user.pending_notifications.mark_read', [
            'user_id' => $user->id,
            'delivery_id' => (int) $id,
            'status' => $delivery->delivery_status,
        ]);

        return $this->successResponse([
            'id' => (int) $id,
            'delivery_status' => $delivery->delivery_status,
        ]);
    }
}
