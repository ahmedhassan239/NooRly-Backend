<?php

namespace App\Domain\Journey\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Journey\JourneyWeek;
use App\Support\Icons\PublicIconsRegistry;
use App\Domain\Lessons\LessonCompletion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JourneyService
{
    private const PLAN_TITLE = '60-Day Learning Path';

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

            return array_merge([
                'id' => $week->id,
                'week_number' => $week->week_number,
                'title' => $week->getTitleForLocale($locale),
                'description' => $week->getDescriptionForLocale($locale),
                'is_current' => (int) $week->week_number === (int) $currentWeekNumber,
                'done' => $doneLessons,
                'total' => $totalLessons,
                'days' => $days,
            ], PublicIconsRegistry::expand($week->icon));
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

        return array_merge([
            'id' => $week->id,
            'week_number' => $week->week_number,
            'title' => $week->getTitleForLocale($locale),
            'description' => $week->getDescriptionForLocale($locale),
            'days' => $days,
        ], PublicIconsRegistry::expand($week->icon));
    }

    /**
     * Get the user's current lesson (first non-completed in journey order).
     * Returns null if all lessons are completed or journey has no lessons.
     *
     * @return array{lesson_id: int, title: string, day: int, week: int, estimated_read_time: int, status: string}|null
     */
    public function getCurrentLessonForUser(AppUser $user, string $locale = 'en'): ?array
    {
        $weeks = JourneyWeek::where('is_active', true)
            ->with([
                'journeyWeekLessons' => fn ($q) => $q->orderBy('sort_order'),
                'journeyWeekLessons.lesson.translations',
            ])
            ->orderBy('week_number')
            ->get();

        $completedLessonIds = $this->getCompletedLessonIds($user);
        $allPivotLessons = $this->collectAllOrderedLessons($weeks);

        /** @var array{week: \App\Domain\Journey\JourneyWeek, pivot: \App\Domain\Journey\JourneyWeekLesson, lesson: \App\Domain\Lessons\Lesson} $p */
        foreach ($allPivotLessons as $p) {
            $week = $p['week'];
            $pivot = $p['pivot'];
            $lesson = $p['lesson'];

            if ($completedLessonIds->contains((string) $lesson->id)) {
                continue;
            }

            $translation = $lesson->translations()
                ->where('language_code', $locale)
                ->first() ?? $lesson->translations()->where('language_code', 'en')->first();

            return [
                'lesson_id' => (int) $lesson->id,
                'title' => $translation?->title ?? $lesson->title ?? 'N/A',
                'day' => (int) $pivot->day_number,
                'week' => (int) $week->week_number,
                'estimated_read_time' => (int) $lesson->duration_minutes,
                'status' => 'current',
            ];
        }

        return null;
    }

    private const TOTAL_DAYS = 60;

    /**
     * Get journey profile summary for the authenticated user.
     * Never throws; returns safe defaults if journey data is missing.
     *
     * @return array{day_index: int, total_days: int, streak_days: int, active_weeks: int, left_days: int, completed_lessons: int, total_lessons: int, completion_percent: float, milestones: array, current_lesson: array|null}
     */
    public function getSummaryForUser(AppUser $user, string $locale = 'en'): array
    {
        try {
            $weeks = JourneyWeek::where('is_active', true)
                ->with([
                    'journeyWeekLessons' => fn ($q) => $q->orderBy('sort_order'),
                    'journeyWeekLessons.lesson.translations',
                ])
                ->orderBy('week_number')
                ->get();

            $completedLessonIds = $this->getCompletedLessonIds($user);
            $allPivotLessons = $this->collectAllOrderedLessons($weeks);
            $totalLessons = $allPivotLessons->count();
            $journeyLessonIds = $allPivotLessons->map(fn (array $p) => (string) $p['lesson']->id)->all();
            $completedInJourney = $completedLessonIds->filter(fn ($id) => in_array((string) $id, $journeyLessonIds, true));
            $completedLessons = $completedInJourney->count();

            $dayIndex = min($completedLessons + 1, self::TOTAL_DAYS);
            if ($totalLessons === 0) {
                $dayIndex = 1;
            }
            $leftDays = max(0, self::TOTAL_DAYS - $dayIndex);
            $completionPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0.0;

            $currentLesson = $this->getCurrentLessonForUser($user, $locale);

            $activeWeeks = $this->computeActiveWeeks($weeks, $completedLessonIds);
            $streakDays = $this->computeStreakDays($user);
            $milestones = $this->computeMilestones($weeks, $completedLessonIds, $currentLesson);

            return [
                'day_index' => $dayIndex,
                'total_days' => self::TOTAL_DAYS,
                'streak_days' => $streakDays,
                'active_weeks' => $activeWeeks,
                'left_days' => $leftDays,
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
                'completion_percent' => $completionPercent,
                'milestones' => $milestones,
                'current_lesson' => $currentLesson,
            ];
        } catch (\Throwable $e) {
            report($e);

            return $this->defaultSummary();
        }
    }

    /**
     * @return array{day_index: int, total_days: int, streak_days: int, active_weeks: int, left_days: int, completed_lessons: int, total_lessons: int, completion_percent: float, milestones: array, current_lesson: null}
     */
    private function defaultSummary(): array
    {
        return [
            'day_index' => 1,
            'total_days' => self::TOTAL_DAYS,
            'streak_days' => 0,
            'active_weeks' => 0,
            'left_days' => self::TOTAL_DAYS - 1,
            'completed_lessons' => 0,
            'total_lessons' => self::TOTAL_DAYS,
            'completion_percent' => 0.0,
            'milestones' => [],
            'current_lesson' => null,
        ];
    }

    private function computeStreakDays(AppUser $user): int
    {
        $dates = LessonCompletion::where('app_user_id', $user->id)
            ->get()
            ->map(fn (LessonCompletion $c) => $c->completed_at?->timezone(config('app.timezone', 'UTC'))->format('Y-m-d'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($dates === []) {
            return 0;
        }

        $today = Carbon::now()->timezone(config('app.timezone', 'UTC'))->format('Y-m-d');
        $endDate = in_array($today, $dates, true) ? $today : $dates[array_key_last($dates)];

        $streak = 0;
        $check = Carbon::parse($endDate)->timezone(config('app.timezone', 'UTC'));
        $dateSet = array_flip($dates);

        while (isset($dateSet[$check->format('Y-m-d')])) {
            $streak++;
            $check->subDay();
        }

        return $streak;
    }

    private function computeActiveWeeks(Collection $weeks, Collection $completedLessonIds): int
    {
        $weeksWithProgress = 0;
        foreach ($weeks as $week) {
            $hasAny = $week->journeyWeekLessons->contains(
                fn ($p) => $completedLessonIds->contains((string) $p->lesson_id)
            );
            if ($hasAny) {
                $weeksWithProgress++;
            }
        }

        return $weeksWithProgress;
    }

    /**
     * @return array<int, array{week: int, status: string, completed_lessons: int, total_lessons: int}>
     */
    private function computeMilestones(Collection $weeks, Collection $completedLessonIds, ?array $currentLesson): array
    {
        $currentWeekNumber = $currentLesson !== null ? $currentLesson['week'] : null;

        $out = [];
        foreach ($weeks as $week) {
            $totalInWeek = $week->journeyWeekLessons->count();
            $doneInWeek = $week->journeyWeekLessons->filter(
                fn ($p) => $completedLessonIds->contains((string) $p->lesson_id)
            )->count();

            if ($doneInWeek >= $totalInWeek && $totalInWeek > 0) {
                $status = 'completed';
            } elseif ((int) $week->week_number === (int) $currentWeekNumber) {
                $status = 'in_progress';
            } else {
                $status = 'locked';
            }

            $out[] = [
                'week' => (int) $week->week_number,
                'status' => $status,
                'completed_lessons' => $doneInWeek,
                'total_lessons' => $totalInWeek,
            ];
        }

        return $out;
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
