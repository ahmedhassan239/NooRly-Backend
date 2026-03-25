<?php

namespace App\Domain\Lessons;

use Illuminate\Database\Eloquent\Model;
use App\Support\Html\LegacyTiptapHtmlNormalizer;

class LessonTranslation extends Model
{
    protected $fillable = [
        'lesson_id',
        'language_code',
        'title',
        'slug',
        'short_description',
        'content',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function setContentAttribute($value): void
    {
        $content = is_string($value) ? $value : null;
        $lang = (string) ($this->attributes['language_code'] ?? $this->language_code ?? '');

        $this->attributes['content'] = $lang === 'ar'
            ? LegacyTiptapHtmlNormalizer::normalizeLegacyArabicHtml($content)
            : LegacyTiptapHtmlNormalizer::stripInlineBlackTextColor($content);
    }
}
