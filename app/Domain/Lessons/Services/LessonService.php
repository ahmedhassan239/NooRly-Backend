<?php

namespace App\Domain\Lessons\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Lessons\LessonCompletion;
use App\Domain\Lessons\LessonReflection;
use Carbon\Carbon;

class LessonService
{
    public function __construct(
        private readonly LessonDatasetService $datasetService
    ) {}

    /**
     * Resolve the current day of the user's journey.
     */
    public function getUserCurrentDay(AppUser $user): int
    {
        $onboarding = $user->onboarding;

        if (!$onboarding || !$onboarding->start_date) {
            return 1;
        }

        $startDate = $onboarding->start_date;
        $diff = $startDate->diffInDays(now());

        $currentDay = $diff + 1;

        return min(90, max(1, $currentDay));
    }

    /**
     * Get the lesson for today.
     */
    public function getTodayLesson(AppUser $user, string $locale = 'en'): ?array
    {
        $currentDay = $this->getUserCurrentDay($user);
        return $this->datasetService->findByDay($currentDay, $locale);
    }

    /**
     * Enrich a lesson with user-specific state.
     */
    public function enrichLessonWithState(AppUser $user, array $lesson): array
    {
        $currentDay = $this->getUserCurrentDay($user);
        $lessonDay = (int)$lesson['day_number'];

        $completion = LessonCompletion::where('app_user_id', $user->id)
            ->where('lesson_id', $lesson['id'])
            ->first();

        $reflection = LessonReflection::where('app_user_id', $user->id)
            ->where('lesson_id', $lesson['id'])
            ->first();

        $lesson['is_unlocked'] = $lessonDay <= $currentDay;
        $lesson['is_completed'] = $completion !== null;
        $lesson['completed_at'] = $completion?->completed_at?->toIso8601String();
        $lesson['reflection_text'] = $reflection?->reflection_text;

        return $lesson;
    }

    /**
     * Mark a lesson as complete for a user.
     */
    public function completeLesson(AppUser $user, string $lessonId): LessonCompletion
    {
        return LessonCompletion::updateOrCreate(
            ['app_user_id' => $user->id, 'lesson_id' => $lessonId],
            ['completed_at' => now()]
        );
    }

    /**
     * Set/Update reflection for a lesson.
     */
    public function saveReflection(AppUser $user, string $lessonId, string $text): LessonReflection
    {
        return LessonReflection::updateOrCreate(
            ['app_user_id' => $user->id, 'lesson_id' => $lessonId],
            ['reflection_text' => $text]
        );
    }

    /**
     * Get progress metrics for a user.
     */
    public function getProgress(AppUser $user): array
    {
        $completedCount = LessonCompletion::where('app_user_id', $user->id)->count();
        $currentDay = $this->getUserCurrentDay($user);
        
        // Find next lesson id (simplified)
        $nextDay = min(90, $currentDay + 1);
        $nextLesson = $this->datasetService->findByDay($nextDay);

        return [
            'completed_count' => $completedCount,
            'current_day' => $currentDay,
            'next_lesson_id' => $nextLesson['id'] ?? null,
            'total_days' => 90,
        ];
    }
}
