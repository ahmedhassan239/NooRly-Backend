<?php

namespace App\Domain\Lessons\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LessonDatasetService
{
    private const CACHE_PREFIX = 'content_lessons_';

    private const CONTENT_PATH = 'content/lessons/';

    /**
     * Get all lessons for a given locale.
     * Tries requested locale, then APP_FALLBACK_LOCALE (or 'ar'), then 'ar'.
     * Returns empty array when no dataset exists (no exception).
     */
    public function getAll(string $locale = 'en'): array
    {
        $fallbackLocale = config('app.fallback_locale') ?: 'ar';
        $candidates = array_values(array_unique([$locale, $fallbackLocale, 'ar']));

        $pathToLoad = null;
        $localeUsed = null;
        foreach ($candidates as $candidate) {
            $path = self::CONTENT_PATH.$candidate.'.json';
            if (Storage::exists($path)) {
                $pathToLoad = $path;
                $localeUsed = $candidate;
                break;
            }
        }

        if ($pathToLoad === null) {
            Log::warning('Lesson dataset not found for locale or fallback', [
                'requested' => $locale,
                'candidates' => $candidates,
            ]);

            return [];
        }

        if ($localeUsed !== $locale) {
            Log::info('Lesson dataset: using fallback locale', [
                'requested' => $locale,
                'used' => $localeUsed,
            ]);
        }

        return Cache::rememberForever(self::CACHE_PREFIX.$locale, function () use ($pathToLoad) {
            $content = Storage::get($pathToLoad);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in lesson dataset: '.$pathToLoad);
            }

            return $data;
        });
    }

    /**
     * Get a specific lesson by ID.
     */
    public function findById(string $id, string $locale = 'en'): ?array
    {
        $lessons = $this->getAll($locale);

        foreach ($lessons as $lesson) {
            if ($lesson['id'] === $id) {
                return $lesson;
            }
        }

        return null;
    }

    /**
     * Get a lesson by day number.
     */
    public function findByDay(int $dayNumber, string $locale = 'en'): ?array
    {
        $lessons = $this->getAll($locale);

        foreach ($lessons as $lesson) {
            if ((int) $lesson['day_number'] === $dayNumber) {
                return $lesson;
            }
        }

        return null;
    }

    /**
     * Clear the cache for the lesson dataset.
     */
    public function clearCache(?string $locale = null): void
    {
        if ($locale) {
            Cache::forget(self::CACHE_PREFIX.$locale);
        } else {
            // In a real app, we might want to iterate over active languages
            Cache::forget(self::CACHE_PREFIX.'en');
            Cache::forget(self::CACHE_PREFIX.'ar');
        }
    }
}
