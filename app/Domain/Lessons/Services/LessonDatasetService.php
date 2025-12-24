<?php

namespace App\Domain\Lessons\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Exception;

class LessonDatasetService
{
    private const CACHE_PREFIX = 'content_lessons_';
    private const CONTENT_PATH = 'content/lessons/';

    /**
     * Get all lessons for a given locale.
     */
    public function getAll(string $locale = 'en'): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $locale, function () use ($locale) {
            $path = self::CONTENT_PATH . $locale . '.json';
            
            if (!Storage::exists($path)) {
                if ($locale !== 'en') {
                    return $this->getAll('en');
                }
                throw new Exception("Lesson dataset not found for locale: " . $locale);
            }

            $content = Storage::get($path);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in lesson dataset: " . $locale);
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
            if ((int)$lesson['day_number'] === $dayNumber) {
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
            Cache::forget(self::CACHE_PREFIX . $locale);
        } else {
            // In a real app, we might want to iterate over active languages
            Cache::forget(self::CACHE_PREFIX . 'en');
            Cache::forget(self::CACHE_PREFIX . 'ar');
        }
    }
}
