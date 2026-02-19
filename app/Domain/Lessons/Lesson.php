<?php

namespace App\Domain\Lessons;

use App\Domain\Concerns\Pivots\HadithItemable;
use App\Domain\Concerns\Pivots\QuranAyahable;
use App\Domain\Hadith\Models\HadithItem;
use App\Domain\Journey\JourneyWeek;
use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Lesson extends Model
{
    use HasFactory, HasTranslations;

    protected static function newFactory()
    {
        return \Database\Factories\LessonFactory::new();
    }

    protected $fillable = [
        'title',
        'content',
        'type',
        'video_url',
        'duration_minutes',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    // HasTranslations implementation
    protected function getTranslationTable(): string
    {
        return 'lesson_translations';
    }

    protected function getTranslationForeignKey(): string
    {
        return 'lesson_id';
    }

    protected function getTranslatableFields(): array
    {
        return ['title', 'short_description', 'content'];
    }

    // Relationships
    public function translations()
    {
        return $this->hasMany(LessonTranslation::class);
    }

    /**
     * Get all Quran verses (Ayat) attached to this lesson.
     */
    public function quranAyahs(): MorphToMany
    {
        return $this->morphToMany(
            QuranVerse::class,
            'ayahable',
            'quran_ayahables',
            'ayahable_id',
            'quran_ayah_id'
        )->using(QuranAyahable::class)->withTimestamps();
    }

    /**
     * Get all Hadith items attached to this lesson.
     */
    public function hadithItems(): MorphToMany
    {
        return $this->morphToMany(
            HadithItem::class,
            'hadithable',
            'hadith_itemables',
            'hadithable_id',
            'hadith_item_id'
        )->using(HadithItemable::class)->withTimestamps();
    }

    /**
     * Journey weeks this lesson belongs to (via pivot with day_number).
     */
    public function journeyWeeks(): BelongsToMany
    {
        return $this->belongsToMany(JourneyWeek::class, 'journey_week_lessons')
            ->using(JourneyWeekLesson::class)
            ->withPivot(['day_number', 'position', 'sort_order'])
            ->withTimestamps();
    }

    public function isWeekReflection(): bool
    {
        return $this->type === 'week_reflection';
    }
}
