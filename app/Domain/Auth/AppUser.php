<?php

namespace App\Domain\Auth;

use App\Domain\Lessons\Lesson;
use App\Domain\Progress\DailyStreak;
use App\Domain\Tasks\DailyTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\AppUserFactory::new();
    }

    protected $table = 'app_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'date_of_birth',
        'shahada_date',
        'main_goal',
        'timezone',
        'country',
        'current_day',
        'is_guest',
        'registration_method',
        'status',
        'onboarding_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'shahada_date' => 'date',
            'is_guest' => 'boolean',
            'registration_method' => \App\Domain\Auth\Enums\RegistrationMethod::class,
            'status' => \App\Domain\Auth\Enums\UserStatus::class,
            'onboarding_completed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(AppUserDeviceToken::class);
    }

    public function streak()
    {
        return $this->hasOne(DailyStreak::class, 'user_id');
    }

    public function completedLessons()
    {
        return $this->belongsToMany(Lesson::class, 'user_lessons', 'user_id', 'lesson_id')->withTimestamps();
    }

    public function completedTasks()
    {
        return $this->belongsToMany(DailyTask::class, 'user_daily_tasks', 'user_id', 'daily_task_id')->withTimestamps();
    }
}
