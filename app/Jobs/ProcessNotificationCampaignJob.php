<?php

namespace App\Jobs;

use App\Domain\Notifications\Campaigns\NotificationCampaign;
use App\Domain\Notifications\Campaigns\NotificationCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotificationCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $campaignId,
    ) {}

    public function handle(NotificationCampaignService $service): void
    {
        $campaign = NotificationCampaign::query()->find($this->campaignId);
        if (! $campaign) {
            return;
        }

        try {
            $service->runProcessing($campaign);
        } catch (\Throwable $e) {
            Log::error('notification_campaign.job_failed', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
            ]);
            $campaign->update([
                'status' => 'failed',
                'processed_at' => now(),
            ]);
            throw $e;
        }
    }
}
