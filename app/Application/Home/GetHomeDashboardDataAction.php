<?php

namespace App\Application\Home;

use App\Domain\Auth\AppUser;
use App\Domain\Duas\Dua;
use App\Domain\Lessons\Lesson;
use App\Domain\Prayers\Contracts\PrayerTimeProvider;
use App\Domain\Tasks\DailyTask;
use Carbon\Carbon;

class GetHomeDashboardDataAction
{
    public function __construct(
        protected PrayerTimeProvider $prayerTimeProvider
    ) {}

    public function execute(AppUser $user): array
    {
        $today = Carbon::now($user->timezone ?? 'UTC');

        // 1. Get Daily Lesson
        $lesson = Lesson::where('day_number', $user->current_day)->first();
        $isLessonCompleted = $user->completedLessons()->where('lesson_id', $lesson?->id)->exists();

        // 2. Get Daily Tasks
        $tasks = DailyTask::where('day_number', $user->current_day)->get();
        // In real app, check completion status for each task

        // 3. Get Dua of the Day (Random or specific logic)
        $dua = Dua::inRandomOrder()->first();

        // 4. Get Prayer Times
        $prayerTimes = $this->prayerTimeProvider->getTimesForUser($user, $today);

        return [
            'day_number' => $user->current_day,
            'lesson' => $lesson, // DTO transformation happens in Resource
            'lesson_completed' => $isLessonCompleted,
            'tasks' => $tasks,
            'dua' => $dua,
            'prayer_times' => $prayerTimes,
            'streak' => $user->streak?->current_streak ?? 0,
        ];
    }
}
