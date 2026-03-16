<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Services\InactivityNotificationService;
use Illuminate\Console\Command;

class DetectInactiveUsersCommand extends Command
{
    protected $signature = 'notifications:inactive
                            {--dry-run : Preview without storing}';

    protected $description = 'Detect inactive users (3/7 days) and generate support notifications';

    public function handle(InactivityNotificationService $service): int
    {
        $this->info('[notifications:inactive] Scanning for inactive users...');

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode: no notifications will be stored');
            return self::SUCCESS;
        }

        try {
            $service->detectAndNotify();
            $this->info('[notifications:inactive] Done.');
        } catch (\Throwable $e) {
            $this->error('[notifications:inactive] Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
