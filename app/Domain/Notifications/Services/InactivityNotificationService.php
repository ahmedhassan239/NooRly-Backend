<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Notifications\Channels\NotificationChannelInterface;
use App\Domain\Notifications\ScheduledNotification;
use App\Domain\Notifications\UserNotificationPreference;
use Carbon\Carbon;

class InactivityNotificationService
{
    public function __construct(
        private readonly NotificationChannelInterface $channel,
    ) {}

    /**
     * Detect inactive users and generate support notifications.
     * Called by the daily Artisan command.
     */
    public function detectAndNotify(): void
    {
        $threeDaysAgo  = Carbon::now()->subDays(3);
        $sevenDaysAgo  = Carbon::now()->subDays(7);
        $eightDaysAgo  = Carbon::now()->subDays(8);

        // 7-day inactive users (higher priority — check first)
        AppUser::query()
            ->where('last_active_at', '<=', $sevenDaysAgo)
            ->where('last_active_at', '>', $eightDaysAgo)
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $this->notify7Days($user);
                }
            });

        // 3-day inactive users
        $fourDaysAgo = Carbon::now()->subDays(4);
        AppUser::query()
            ->where('last_active_at', '<=', $threeDaysAgo)
            ->where('last_active_at', '>', $fourDaysAgo)
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $this->notify3Days($user);
                }
            });
    }

    private function notify3Days(AppUser $user): void
    {
        if (! $this->isEligible($user, 'support_inactive_3_days', 3)) {
            return;
        }

        $notification = ScheduledNotification::create([
            'user_id'       => $user->id,
            'category'      => 'support',
            'sub_type'      => 'support_inactive_3_days',
            'title_ar'      => '💔 نفتقدك!',
            'title_en'      => '💔 We Miss You!',
            'body_ar'       => "لم نرك منذ 3 أيام\nهل كل شيء بخير؟",
            'body_en'       => "We haven't seen you for 3 days\nIs everything okay?",
            'scheduled_for' => Carbon::now(),
            'status'        => 'pending',
            'payload'       => ['route' => '/home'],
        ]);

        $this->channel->send($notification);
    }

    private function notify7Days(AppUser $user): void
    {
        if (! $this->isEligible($user, 'support_inactive_7_days', 7)) {
            return;
        }

        $notification = ScheduledNotification::create([
            'user_id'       => $user->id,
            'category'      => 'support',
            'sub_type'      => 'support_inactive_7_days',
            'title_ar'      => '🤲 نحن هنا لك',
            'title_en'      => '🤲 We\'re Here for You',
            'body_ar'       => "إذا كنت تواجه صعوبة، دعنا نساعدك\nاضغط للحصول على دعم",
            'body_en'       => "If you're struggling, let us help\nTap for support",
            'scheduled_for' => Carbon::now(),
            'status'        => 'pending',
            'payload'       => ['route' => '/need-help'],
        ]);

        $this->channel->send($notification);
    }

    private function isEligible(AppUser $user, string $subType, int $days): bool
    {
        $prefs = UserNotificationPreference::where('user_id', $user->id)->first();
        if ($prefs && ! $prefs->support_reminders_enabled) {
            return false;
        }

        // Don't send if already sent in the past $days days
        return ! ScheduledNotification::query()
            ->where('user_id', $user->id)
            ->where('sub_type', $subType)
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }
}
