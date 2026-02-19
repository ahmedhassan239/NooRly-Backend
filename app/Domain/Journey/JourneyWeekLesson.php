<?php

namespace App\Domain\Journey;

use App\Domain\Lessons\Lesson;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JourneyWeekLesson extends Pivot
{
    protected $table = 'journey_week_lessons';

    public $incrementing = true;

    protected $fillable = [
        'journey_week_id',
        'lesson_id',
        'day_number',
        'position',
        'sort_order',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'position' => 'integer',
        'sort_order' => 'integer',
    ];

    public function journeyWeek(): BelongsTo
    {
        return $this->belongsTo(JourneyWeek::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    protected static function booted(): void
    {
        static::saving(function (JourneyWeekLesson $pivot): void {
            if ($pivot->day_number !== null && $pivot->position !== null) {
                $pivot->sort_order = (int) $pivot->day_number * 100 + (int) $pivot->position;
            }
        });
    }
}
