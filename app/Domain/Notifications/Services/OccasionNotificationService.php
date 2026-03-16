<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Notifications\Channels\NotificationChannelInterface;
use App\Domain\Notifications\ScheduledNotification;
use App\Domain\Notifications\UserNotificationPreference;
use Carbon\Carbon;

class OccasionNotificationService
{
    public function __construct(
        private readonly NotificationChannelInterface $channel,
    ) {}

    /**
     * Generate occasion notifications for all users.
     * Called by the daily Artisan command.
     */
    public function generateForAllUsers(): void
    {
        $today = Carbon::now();

        AppUser::query()
            ->whereNotNull('id')
            ->chunk(100, function ($users) use ($today) {
                foreach ($users as $user) {
                    $this->generateForUser($user, $today);
                }
            });
    }

    public function generateForUser(AppUser $user, Carbon $today): void
    {
        $prefs = UserNotificationPreference::where('user_id', $user->id)->first();

        if ($prefs && ! $prefs->special_occasions_enabled) {
            return;
        }

        // Friday reminder
        if ($today->isDayOfWeek(Carbon::FRIDAY)) {
            $this->createOccasionNotification(
                $user,
                'friday',
                '🕌 الجمعة المباركة',
                "لا تنسَ صلاة الجمعة اليوم\nاقرأ سورة الكهف 📖",
                '🕌 Blessed Friday',
                "Don't forget Jumu'ah prayer today\nRead Surah Al-Kahf 📖",
                $today->setTime(8, 0),
                '/home',
            );
        }

        // Ramadan approaching check (approximate: within 7 days)
        $this->checkRamadanApproaching($user, $today);
    }

    private function checkRamadanApproaching(AppUser $user, Carbon $today): void
    {
        // Basic Gregorian-based Ramadan estimate (replace with Hijri library for precision)
        // This is a placeholder — real implementation needs a Hijri date library
        // e.g., using islamicNetwork/hijri-date or similar
        // For now, generate based on manual check
    }

    private function createOccasionNotification(
        AppUser $user,
        string $subType,
        string $titleAr,
        string $bodyAr,
        string $titleEn,
        string $bodyEn,
        Carbon $scheduledFor,
        string $route,
    ): void {
        // Don't duplicate within 6 days
        $alreadyExists = ScheduledNotification::query()
            ->where('user_id', $user->id)
            ->where('sub_type', "occasion_{$subType}")
            ->where('created_at', '>=', now()->subDays(6))
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $notification = ScheduledNotification::create([
            'user_id'       => $user->id,
            'category'      => 'occasion',
            'sub_type'      => "occasion_{$subType}",
            'title_ar'      => $titleAr,
            'title_en'      => $titleEn,
            'body_ar'       => $bodyAr,
            'body_en'       => $bodyEn,
            'scheduled_for' => $scheduledFor,
            'status'        => 'pending',
            'payload'       => ['route' => $route],
        ]);

        $this->channel->send($notification);
    }
}
