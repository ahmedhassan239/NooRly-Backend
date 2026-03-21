<?php

namespace App\Domain\Notifications\Campaigns;

use App\Domain\Notifications\Push\PushProviderInterface;
use App\Domain\Notifications\UserNotificationToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class NotificationCampaignService
{
    public function __construct(
        private readonly NotificationAudienceResolver $audienceResolver,
        private readonly NotificationLocalizedContentResolver $localizedContentResolver,
        private readonly PushProviderInterface $pushProvider,
    ) {}

    public function createDraft(array $data, ?int $createdByUserId): NotificationCampaign
    {
        return NotificationCampaign::query()->create(array_merge($data, [
            'created_by' => $createdByUserId,
            'status' => $data['status'] ?? 'draft',
        ]));
    }

    public function schedule(NotificationCampaign $campaign, \DateTimeInterface $when): void
    {
        $campaign->update([
            'send_mode' => 'scheduled',
            'scheduled_for' => $when,
            'status' => 'scheduled',
        ]);
    }

    public function cancel(NotificationCampaign $campaign): void
    {
        if (! $campaign->isCancellable()) {
            return;
        }
        $campaign->update(['status' => 'cancelled']);
    }

    /**
     * Queue or run campaign processing (idempotent for final states).
     */
    public function dispatchProcess(NotificationCampaign $campaign): void
    {
        if (in_array($campaign->status, ['cancelled', 'processing', 'sent', 'partial', 'failed'], true)) {
            return;
        }

        \App\Jobs\ProcessNotificationCampaignJob::dispatch($campaign->id);
    }

    public function processNow(NotificationCampaign $campaign): void
    {
        $this->runProcessing($campaign);
    }

    public function runProcessing(NotificationCampaign $campaign): void
    {
        if ($campaign->status === 'cancelled') {
            return;
        }

        if (in_array($campaign->status, ['sent', 'partial', 'failed'], true) && $campaign->processed_at !== null) {
            return;
        }

        DB::transaction(function () use ($campaign) {
            /** @var NotificationCampaign|null $locked */
            $locked = NotificationCampaign::query()->whereKey($campaign->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === 'cancelled') {
                return;
            }

            if (in_array($locked->status, ['sent', 'partial', 'failed'], true) && $locked->processed_at !== null) {
                return;
            }

            $locked->update(['status' => 'processing']);
            $campaign = $locked;

            $users = $this->audienceResolver->resolve($campaign->audience_type, $campaign->audience_filters);

            $sent = 0;
            $failed = 0;
            $skipped = 0;
            $pendingPull = 0;

            $terminalDeliveryStatuses = [
                'sent',
                'failed',
                'skipped',
                'pending_for_app_pull',
                'shown_locally',
                'read',
                'expired',
                'provider_unavailable',
            ];

            foreach ($users as $user) {
                $user->loadMissing('settings');

                $delivery = NotificationCampaignDelivery::query()->firstOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'user_id' => $user->id,
                    ],
                    ['delivery_status' => 'pending']
                );

                if (in_array($delivery->delivery_status, $terminalDeliveryStatuses, true)) {
                    continue;
                }

                if ($user->settings && $user->settings->notifications_enabled === false) {
                    $delivery->update([
                        'delivery_status' => 'skipped',
                        'failure_reason' => 'notifications_disabled',
                    ]);
                    $skipped++;

                    continue;
                }

                $payload = $this->localizedContentResolver->resolveForUser(
                    $user,
                    $campaign->title_ar,
                    $campaign->title_en,
                    $campaign->body_ar,
                    $campaign->body_en,
                );

                $token = UserNotificationToken::query()
                    ->where('user_id', $user->id)
                    ->active()
                    ->orderByDesc('last_seen_at')
                    ->first();

                $data = array_filter([
                    'route' => $campaign->route,
                    'campaign_id' => (string) $campaign->id,
                    'type' => $campaign->type,
                    'image_url' => $campaign->image_url,
                ], fn ($v) => $v !== null && $v !== '');

                $pushResult = $this->pushProvider->sendToUser($user, $payload, $data);

                if ($this->pushProvider->isConfigured() && ($pushResult['success'] ?? false)) {
                    $delivery->update([
                        'delivery_status' => 'sent',
                        'platform' => $token?->platform,
                        'provider' => $this->pushProvider->name(),
                        'provider_message_id' => $pushResult['provider_message_id'] ?? null,
                        'failure_reason' => null,
                        'sent_at' => now(),
                    ]);
                    $sent++;
                } elseif ($this->pushProvider->isConfigured()) {
                    $reason = $pushResult['error'] ?? 'push_failed';
                    $delivery->update([
                        'delivery_status' => 'failed',
                        'platform' => $token?->platform,
                        'provider' => $this->pushProvider->name(),
                        'failure_reason' => $reason,
                        'sent_at' => null,
                    ]);
                    $failed++;
                } else {
                    $reason = $pushResult['error'] ?? 'awaiting_app_pull';
                    $delivery->update([
                        'delivery_status' => 'pending_for_app_pull',
                        'platform' => $token?->platform,
                        'provider' => $this->pushProvider->name(),
                        'failure_reason' => $reason,
                        'sent_at' => null,
                    ]);
                    $pendingPull++;
                }

                NotificationInbox::query()->firstOrCreate(
                    ['delivery_id' => $delivery->id],
                    [
                        'user_id' => $user->id,
                        'campaign_id' => $campaign->id,
                        'title_ar' => $campaign->title_ar,
                        'title_en' => $campaign->title_en,
                        'body_ar' => $campaign->body_ar,
                        'body_en' => $campaign->body_en,
                        'route' => $campaign->route,
                        'is_read' => false,
                    ]
                );
            }

            $finalStatus = 'sent';
            if ($failed > 0) {
                $finalStatus = ($sent > 0 || $pendingPull > 0 || $skipped > 0) ? 'partial' : 'failed';
            } elseif ($sent === 0 && $pendingPull === 0 && $skipped === 0) {
                $finalStatus = 'failed';
            } elseif ($skipped > 0 && ($sent > 0 || $pendingPull > 0)) {
                $finalStatus = 'partial';
            } elseif ($sent === 0 && $pendingPull === 0 && $skipped > 0) {
                $finalStatus = 'partial';
            }

            $campaign->update([
                'status' => $finalStatus,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'skipped_count' => $skipped,
                'pending_app_pull_count' => $pendingPull,
                'processed_at' => now(),
            ]);

            Log::info('notification_campaign.processed', [
                'campaign_id' => $campaign->id,
                'sent' => $sent,
                'failed' => $failed,
                'skipped' => $skipped,
                'pending_app_pull' => $pendingPull,
                'status' => $finalStatus,
            ]);
        });
    }

}
