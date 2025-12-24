<?php

namespace App\Domain\Lessons;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class LessonCompletion extends Model
{
    protected $table = 'lesson_completions';

    protected $fillable = [
        'app_user_id',
        'lesson_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
