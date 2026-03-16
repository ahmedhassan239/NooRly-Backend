<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Notifications\Channels\NotificationChannelInterface;
use App\Domain\Notifications\ScheduledNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MilestoneNotificationService
{
    // Day milestones to celebrate
    private const MILESTONE_DAYS = [1, 7, 14, 21, 30, 60];

    // Prayer streak milestones
    private const PRAYER_STREAK_MILESTONES = [5, 10, 20, 50];

    public function __construct(
        private readonly NotificationChannelInterface $channel,
    ) {}

    /**
     * Check all users for pending milestone notifications.
     * Called by the daily Artisan command.
     */
    public function generateForAllUsers(): void
    {
        AppUser::query()
            ->whereNotNull('id')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $this->generateForUser($user);
                }
            });
    }

    public function generateForUser(AppUser $user): void
    {
        $progress = DB::table('user_progress')
            ->where('user_id', $user->id)
            ->first();

        if (! $progress) {
            return;
        }

        $completedDays = (int) ($progress->lessons_completed ?? 0);

        foreach (self::MILESTONE_DAYS as $milestone) {
            if ($completedDays === $milestone) {
                $this->createMilestoneNotification($user, $milestone);
            }
        }
    }

    private function createMilestoneNotification(AppUser $user, int $day): void
    {
        $alreadyExists = ScheduledNotification::query()
            ->where('user_id', $user->id)
            ->where('sub_type', "milestone_day_{$day}")
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $isWeekCompletion = $day % 7 === 0 && $day < 60;
        $week = (int) ($day / 7);

        if ($day === 60) {
            $titleAr = 'تهانينا!';
            $bodyAr  = "أكملت رحلة الـ 60 يومًا\nبارك الله فيك! 💚";
            $titleEn = 'Congratulations!';
            $bodyEn  = "You completed the 60-day journey\nMay Allah bless you! 💚";
        } elseif ($day === 30) {
            $titleAr = 'إنجاز عظيم!';
            $bodyAr  = "أكملت 30 يومًا متواصلة\nنصف الطريق! واصل! 💪";
            $titleEn = 'Amazing Achievement!';
            $bodyEn  = "You completed 30 consecutive days\nHalfway there! Keep going! 💪";
        } elseif ($isWeekCompletion) {
            $titleAr = 'أسبوع كامل!';
            $bodyAr  = "أنهيت الأسبوع {$week}\nاستمر على هذا المنوال! 🔥";
            $titleEn = 'Full Week Complete!';
            $bodyEn  = "You finished Week {$week}\nKeep up the great work! 🔥";
        } else {
            $remaining = 60 - $day;
            $titleAr = 'أحسنت!';
            $bodyAr  = "أكملت اليوم {$day}\n{$remaining} يوم متبقي";
            $titleEn = 'Well Done!';
            $bodyEn  = "You completed Day {$day}\n{$remaining} days remaining";
        }

        $notification = ScheduledNotification::create([
            'user_id'       => $user->id,
            'category'      => 'milestone',
            'sub_type'      => "milestone_day_{$day}",
            'title_ar'      => $titleAr,
            'title_en'      => $titleEn,
            'body_ar'       => $bodyAr,
            'body_en'       => $bodyEn,
            'scheduled_for' => Carbon::now(),
            'status'        => 'pending',
            'payload'       => ['day_number' => $day, 'route' => '/journey'],
        ]);

        $this->channel->send($notification);
    }
}
