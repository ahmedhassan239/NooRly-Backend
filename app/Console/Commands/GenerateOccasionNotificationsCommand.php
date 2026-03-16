<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Services\OccasionNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateOccasionNotificationsCommand extends Command
{
    protected $signature = 'notifications:occasions
                            {--dry-run : Preview without storing}';

    protected $description = 'Generate special occasion notifications (Friday, Ramadan, Eid)';

    public function handle(OccasionNotificationService $service): int
    {
        $this->info('[notifications:occasions] Starting occasion check for ' . Carbon::now()->toDateString() . '...');

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode: no notifications will be stored');
            return self::SUCCESS;
        }

        try {
            $service->generateForAllUsers();
            $this->info('[notifications:occasions] Done.');
        } catch (\Throwable $e) {
            $this->error('[notifications:occasions] Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
