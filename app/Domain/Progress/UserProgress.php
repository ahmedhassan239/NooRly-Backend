<?php

namespace App\Domain\Progress;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    protected $table = 'user_progress';

    protected $fillable = [
        'app_user_id',
        'date',
        'completed_task_ids',
        'salah_completed_step_ids',
        'wudu_completed_step_ids',
        'streak_count',
    ];

    protected $casts = [
        'date' => 'date',
        'completed_task_ids' => 'json',
        'salah_completed_step_ids' => 'json',
        'wudu_completed_step_ids' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
