<?php

namespace App\Domain\Tasks;

use Illuminate\Database\Eloquent\Model;
use App\Support\Html\LegacyTiptapHtmlNormalizer;

class DailyTaskTranslation extends Model
{
    protected $fillable = [
        'daily_task_id',
        'language_code',
        'title',
        'description',
    ];

    public function dailyTask()
    {
        return $this->belongsTo(DailyTask::class);
    }

    public function setDescriptionAttribute($value): void
    {
        $description = is_string($value) ? $value : null;
        $lang = (string) ($this->attributes['language_code'] ?? $this->language_code ?? '');

        $this->attributes['description'] = $lang === 'ar'
            ? LegacyTiptapHtmlNormalizer::normalizeLegacyArabicHtml($description)
            : LegacyTiptapHtmlNormalizer::stripInlineBlackTextColor($description);
    }
}
