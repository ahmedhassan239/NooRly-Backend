<?php

namespace App\Application\Lessons;

use App\Domain\Auth\AppUser;
use App\Domain\Lessons\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompleteLessonAction
{
    public function execute(AppUser $user, Lesson $lesson): void
    {
        if ($user->completedLessons()->where('lesson_id', $lesson->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($user, $lesson) {
            $user->completedLessons()->attach($lesson->id, ['completed_at' => now()]);

            // Update Streak Logic (Simplified)
            $streak = $user->streak()->firstOrCreate();
            $today = Carbon::now($user->timezone)->startOfDay();
            $lastActivity = $streak->last_activity_date ? Carbon::parse($streak->last_activity_date)->startOfDay() : null;

            if ($lastActivity && $lastActivity->diffInDays($today) === 1) {
                // Consecutive day
                $streak->increment('current_streak');
            } elseif (! $lastActivity || $lastActivity->diffInDays($today) > 1) {
                // Streak broken or new
                $streak->update(['current_streak' => 1]);
            }

            $streak->update(['last_activity_date' => $today]);
            if ($streak->current_streak > $streak->max_streak) {
                $streak->update(['max_streak' => $streak->current_streak]);
            }
        });
    }
}
