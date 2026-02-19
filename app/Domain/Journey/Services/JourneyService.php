<?php

namespace App\Domain\Journey\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Lessons\LessonCompletion;
use App\Domain\Journey\JourneyWeek;
use Illuminate\Support\Collection;

class JourneyService
{
    private const PLAN_TITLE = '90-Day Learning Path';

    /**
     * Get journey data for the authenticated user (UI-ready).
     * Returns weeks with days[] and each day has lessons[] (multiple per day allowed).
     */
    public function getJourneyForUser(AppUser $user, string $locale = 'en'): array
    {
        $weeks = JourneyWeek::where('is_active', true)
            ->with([
                'translations',
                'journeyWeekLessons' => fn ($q) => $q->orderBy('sort_order'),
                'journeyWeekLessons.lesson.translations',
            ])
            ->orderBy('week_number')
            ->get();

        $completedLessonIds = $this->getCompletedLessonIds($user);
        $allPivotLessons = $this->collectAllOrderedLessons($weeks);
        $totalDays = $allPivotLessons->count();
        $doneDays = $allPivotLessons->filter(fn (array $p) => $completedLessonIds->contains((string) $p['lesson']->id))->count();
        $progressPercent = $totalDays > 0 ? (int) round(($doneDays / $totalDays) * 100) : 0;

        $currentLessonId = null;
        foreach ($allPivotLessons as $p) {
            /** @var array{lesson: \App\Domain\Lessons\Lesson} $p */
            if (! $completedLessonIds->contains((string) $p['lesson']->id)) {
                $currentLessonId = (string) $p['lesson']->id;
                break;
            }
        }

        $currentWeekNumber = null;
        if ($currentLessonId) {
            foreach ($weeks as $w) {
                $hasCurrent = $w->journeyWeekLessons->contains(
                    fn ($p) => (string) $p->lesson_id === $currentLessonId
                );
                if ($hasCurrent) {
                    $currentWeekNumber = $w->week_number;
                    break;
                }
            }
        } else {
            $currentWeekNumber = $weeks->isEmpty() ? 1 : $weeks->last()->week_number;
        }

        $weeksPayload = $weeks->map(function (JourneyWeek $week) use (
            $completedLessonIds,
            $currentLessonId,
            $currentWeekNumber,
            $locale
        ) {
            $groupedByDay = $week->journeyWeekLessons->sortBy('sort_order')->groupBy('day_number');
            $days = [];
            foreach ($groupedByDay->keys()->sort()->values() as $dayNum) {
                $pivots = $groupedByDay->get($dayNum)->sortBy('position')->values();
                $lessons = $pivots->map(function ($pivot) use ($completedLessonIds, $currentLessonId, $locale) {
                    $lesson = $pivot->lesson;
                    $lessonIdStr = (string) $lesson->id;
                    $isDone = $completedLessonIds->contains($lessonIdStr);
                    $isCurrent = $currentLessonId === $lessonIdStr;
                    $isLocked = ! $isDone && ! $isCurrent;

                    $translation = $lesson->translations()
                        ->where('language_code', $locale)
                        ->first() ?? $lesson->translations()->where('language_code', 'en')->first();

                    return [
                        'id' => $lesson->id,
                        'title' => $translation?->title ?? $lesson->title ?? 'N/A',
                        'minutes' => (int) $lesson->duration_minutes,
                        'category' => null,
                        'type' => $lesson->type ?? 'text',
                        'is_done' => $isDone,
                        'is_current' => $isCurrent,
                        'is_locked' => $isLocked,
                    ];
                })->all();

                $days[] = [
                    'day' => (int) $dayNum,
                    'lessons' => $lessons,
                ];
            }

            $totalLessons = $week->journeyWeekLessons->count();
            $doneLessons = $week->journeyWeekLessons->filter(fn ($p) => $completedLessonIds->contains((string) $p->lesson_id))->count();

            return [
                'id' => $week->id,
                'week_number' => $week->week_number,
                'title' => $week->getTitleForLocale($locale),
                'description' => $week->getDescriptionForLocale($locale),
                'icon' => $week->icon,
                'is_current' => (int) $week->week_number === (int) $currentWeekNumber,
                'done' => $doneLessons,
                'total' => $totalLessons,
                'days' => $days,
            ];
        })->values()->all();

        return [
            'plan' => ['title' => self::PLAN_TITLE],
            'overall' => [
                'total_days' => $totalDays,
                'done_days' => $doneDays,
                'progress_percent' => $progressPercent,
                'current_week' => $currentWeekNumber ?? 1,
            ],
            'weeks' => $weeksPayload,
        ];
    }

    /**
     * Get lessons for a single week (days[] with lessons[]).
     */
    public function getWeekLessons(int $weekNumber, AppUser $user, string $locale = 'en'): ?array
    {
        $week = JourneyWeek::where('is_active', true)
            ->where('week_number', $weekNumber)
            ->with([
                'translations',
                'journeyWeekLessons' => fn ($q) => $q->orderBy('sort_order'),
                'journeyWeekLessons.lesson.translations',
            ])
            ->first();

        if (! $week) {
            return null;
        }

        $completedLessonIds = $this->getCompletedLessonIds($user);
        $groupedByDay = $week->journeyWeekLessons->sortBy('sort_order')->groupBy('day_number');
        $days = [];
        foreach ($groupedByDay->keys()->sort()->values() as $dayNum) {
            $pivots = $groupedByDay->get($dayNum)->sortBy('position')->values();
            $lessons = $pivots->map(function ($pivot) use ($completedLessonIds, $locale) {
                $lesson = $pivot->lesson;
                $lessonIdStr = (string) $lesson->id;
                $translation = $lesson->translations()
                    ->where('language_code', $locale)
                    ->first() ?? $lesson->translations()->where('language_code', 'en')->first();

                return [
                    'id' => $lesson->id,
                    'title' => $translation?->title ?? $lesson->title ?? 'N/A',
                    'minutes' => (int) $lesson->duration_minutes,
                    'category' => null,
                    'type' => $lesson->type ?? 'text',
                    'is_done' => $completedLessonIds->contains($lessonIdStr),
                    'is_locked' => false,
                ];
            })->all();

            $days[] = [
                'day' => (int) $dayNum,
                'lessons' => $lessons,
            ];
        }

        return [
            'id' => $week->id,
            'week_number' => $week->week_number,
            'title' => $week->getTitleForLocale($locale),
            'description' => $week->getDescriptionForLocale($locale),
            'days' => $days,
        ];
    }

    private function getCompletedLessonIds(AppUser $user): Collection
    {
        return LessonCompletion::where('app_user_id', $user->id)
            ->pluck('lesson_id');
    }

    private function collectAllOrderedLessons(Collection $weeks): Collection
    {
        $out = collect();
        foreach ($weeks as $week) {
            foreach ($week->journeyWeekLessons->sortBy('sort_order') as $pivot) {
                $out->push(['week' => $week, 'pivot' => $pivot, 'lesson' => $pivot->lesson]);
            }
        }
        return $out;
    }
}
