<?php

namespace App\Domain\Lessons;

use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory, HasTranslations;

    protected static function newFactory()
    {
        return \Database\Factories\LessonFactory::new();
    }

    protected $fillable = [
        'day_number',
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
}
