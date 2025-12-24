<?php

namespace App\Domain\Lessons;

use Illuminate\Database\Eloquent\Model;

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
}
