<?php

namespace App\Domain\Lessons\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Concerns\Pivots\HadithItemable;
use App\Domain\Concerns\Pivots\QuranAyahable;
use App\Domain\Hadith\Models\HadithItem;
use App\Domain\Lessons\Lesson;
use App\Domain\Lessons\LessonCompletion;
use App\Domain\Lessons\LessonReflection;
use App\Domain\QuranAllLang\Models\QuranVerse;
use Carbon\Carbon;

class LessonService
{
    public function __construct(
        private readonly LessonDatasetService $datasetService
    ) {}

    /**
     * Find a lesson by id for API (DB or dataset). Returns array shape for LessonResource.
     */
    public function findByIdForApi(string $id, string $locale = 'en'): ?array
    {
        if (is_numeric($id)) {
            $lesson = Lesson::with(['translations'])->find((int) $id);
            if (! $lesson) {
                return null;
            }
            $translation = $lesson->translations->where('language_code', $locale)->first()
                ?? $lesson->translations->where('language_code', 'en')->first();
            $firstWeek = $lesson->journeyWeeks()->orderBy('week_number')->first();
            $pivot = $firstWeek?->pivot;
            $dayNumber = $pivot ? (int) $pivot->day_number : 0;
            $weekNumber = $firstWeek ? (int) $firstWeek->week_number : 0;

            $content = $translation?->content ?? $lesson->content;
            $contentString = is_string($content) ? $content : (is_array($content) ? json_encode($content) : '');

            return [
                'id' => (string) $lesson->id,
                'day_number' => $dayNumber,
                'week_number' => $weekNumber,
                'title' => $translation?->title ?? $lesson->title ?? '',
                'summary' => $translation?->short_description ?? '',
                'content' => $contentString,
                'estimated_minutes' => (int) $lesson->duration_minutes,
                'tags' => [],
                'type' => $lesson->type ?? 'text',
                'quran_ayahs' => $this->getQuranAyahsForLesson($lesson, $locale),
                'hadith_items' => $this->getHadithItemsForLesson($lesson),
            ];
        }

        return $this->datasetService->findById($id, $locale);
    }

    /**
     * Get Quran ayahs attached to a lesson, formatted for API response.
     * Loads via pivot on main DB then verses from quran_all_lang to avoid cross-DB join.
     * Returns empty array if pivot/table is missing or query fails.
     */
    private function getQuranAyahsForLesson(Lesson $lesson, string $locale = 'en'): array
    {
        try {
            $ids = QuranAyahable::on('mysql')
                ->where('ayahable_type', Lesson::class)
                ->where('ayahable_id', $lesson->id)
                ->pluck('quran_ayah_id')
                ->all();
            if ($ids === []) {
                return [];
            }
            $ayahs = QuranVerse::on('mysql_quran_all_lang')
                ->with(['verseTexts.translation.language'])
                ->whereIn('id', $ids)
                ->get();

            return $ayahs->map(function ($verse) use ($locale) {
                $verseText = $verse->verseTexts
                    ->first(fn ($vt) => $vt->translation?->language?->code === $locale)
                    ?? $verse->verseTexts->first(fn ($vt) => $vt->translation?->language?->code === 'en')
                    ?? $verse->verseTexts->first();

                return [
                    'surah_number' => (int) $verse->surah_number,
                    'ayah_number' => (int) $verse->ayah_number,
                    'surah_name_en' => \App\Domain\QuranAllLang\Helpers\SurahHelper::getName($verse->surah_number),
                    'text_en' => $verseText?->text ?? '',
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get Hadith items attached to a lesson, formatted for API response.
     * Loads via pivot on main DB then items from hadith connection to avoid cross-DB join.
     * Returns empty array if pivot/table is missing or query fails.
     */
    private function getHadithItemsForLesson(Lesson $lesson): array
    {
        try {
            $hadithConnection = config('content_sources.hadith.connection', 'mysql_hadith');
            $ids = HadithItemable::on('mysql')
                ->where('hadithable_type', Lesson::class)
                ->where('hadithable_id', $lesson->id)
                ->pluck('hadith_item_id')
                ->all();
            if ($ids === []) {
                return [];
            }
            $hadiths = HadithItem::on($hadithConnection)->whereIn('id', $ids)->get();
            $colConfig = config('content_sources.hadith.columns', []);

            return $hadiths->map(function ($hadith) use ($colConfig) {
                $collectionCol = $colConfig['collection'] ?? 'source';
                $hadithNoCol = $colConfig['hadith_number'] ?? 'hadith_no';
                $textEnCol = $colConfig['text_en'] ?? 'text_en';

                return [
                    'collection' => $hadith->{$collectionCol} ?? '',
                    'number' => (string) ($hadith->{$hadithNoCol} ?? ''),
                    'text_en' => $hadith->{$textEnCol} ?? '',
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Check if a lesson exists (DB or dataset).
     */
    public function lessonExists(string $id): bool
    {
        if (is_numeric($id)) {
            return Lesson::where('id', (int) $id)->exists();
        }
        return $this->datasetService->findById($id) !== null;
    }

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
        $lessonDay = (int) ($lesson['day_number'] ?? 0);
        $lessonId = $lesson['id'] ?? null;
        if ($lessonId !== null && is_int($lessonId)) {
            $lessonId = (string) $lessonId;
        }

        $completion = $lessonId
            ? LessonCompletion::where('app_user_id', $user->id)->where('lesson_id', $lessonId)->first()
            : null;

        $reflection = $lessonId
            ? LessonReflection::where('app_user_id', $user->id)->where('lesson_id', $lessonId)->first()
            : null;

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
