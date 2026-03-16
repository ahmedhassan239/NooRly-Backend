<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Services\MilestoneNotificationService;
use Illuminate\Console\Command;

class GenerateMilestoneNotificationsCommand extends Command
{
    protected $signature = 'notifications:milestones
                            {--dry-run : Preview without storing}';

    protected $description = 'Check user journey progress and generate milestone notifications';

    public function handle(MilestoneNotificationService $service): int
    {
        $this->info('[notifications:milestones] Starting milestone check...');

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode: no notifications will be stored');
            return self::SUCCESS;
        }

        try {
            $service->generateForAllUsers();
            $this->info('[notifications:milestones] Done.');
        } catch (\Throwable $e) {
            $this->error('[notifications:milestones] Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
