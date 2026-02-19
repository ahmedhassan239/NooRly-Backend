<?php

namespace App\Domain\Journey;

use App\Domain\Lessons\Lesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class JourneyWeek extends Model
{
    protected $fillable = [
        'week_number',
        'title',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(JourneyWeekTranslation::class);
    }

    public function journeyWeekLessons(): HasMany
    {
        return $this->hasMany(JourneyWeekLesson::class)->orderBy('sort_order');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'journey_week_lessons')
            ->using(JourneyWeekLesson::class)
            ->withPivot(['day_number', 'position', 'sort_order'])
            ->withTimestamps()
            ->orderBy('journey_week_lessons.sort_order');
    }

    /**
     * Get title for a given locale (from translations table).
     */
    public function getTitleForLocale(string $locale = 'en'): string
    {
        $t = $this->translations()->where('language_code', $locale)->first()
            ?? $this->translations()->where('language_code', 'en')->first();
        return $t?->title ?? $this->title ?? 'Week ' . $this->week_number;
    }

    /**
     * Get description for a given locale.
     */
    public function getDescriptionForLocale(string $locale = 'en'): ?string
    {
        $t = $this->translations()->where('language_code', $locale)->first()
            ?? $this->translations()->where('language_code', 'en')->first();
        return $t?->description ?? $this->description;
    }

    /**
     * Lessons grouped by day_number, each day ordered by position.
     */
    public function getLessonsGroupedByDay(): Collection
    {
        return $this->journeyWeekLessons()
            ->with('lesson.translations')
            ->orderBy('day_number')
            ->orderBy('position')
            ->get()
            ->groupBy('day_number');
    }
}
