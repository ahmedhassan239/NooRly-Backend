<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Campaigns\NotificationCampaign;
use App\Jobs\ProcessNotificationCampaignJob;
use Illuminate\Console\Command;

class ProcessScheduledNotificationCampaignsCommand extends Command
{
    protected $signature = 'notifications:process-scheduled-campaigns';

    protected $description = 'Dispatch jobs for notification campaigns scheduled for now or earlier';

    public function handle(): int
    {
        $query = NotificationCampaign::query()
            ->where('status', 'scheduled')
            ->where('send_mode', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now());

        $count = 0;
        foreach ($query->cursor() as $campaign) {
            ProcessNotificationCampaignJob::dispatch($campaign->id);
            $count++;
        }

        if ($count > 0) {
            $this->info("Dispatched {$count} campaign job(s).");
        }

        return self::SUCCESS;
    }
}
