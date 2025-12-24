<?php

namespace App\Domain\Lessons;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class LessonReflection extends Model
{
    protected $table = 'lesson_reflections';

    protected $fillable = [
        'app_user_id',
        'lesson_id',
        'reflection_text',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
