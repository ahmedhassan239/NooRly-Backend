<?php

namespace App\Domain\Users;

use App\Domain\Lessons\Lesson;
use App\Domain\Progress\DailyStreak;
use App\Domain\Tasks\DailyTask;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'shahada_date',
        'goal',
        'timezone',
        'current_day',
        'is_onboarded',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'shahada_date' => 'date',
        'is_onboarded' => 'boolean',
    ];

    public function streak()
    {
        return $this->hasOne(DailyStreak::class);
    }

    public function completedLessons()
    {
        return $this->belongsToMany(Lesson::class, 'user_lessons')->withTimestamps();
    }

    public function completedTasks()
    {
        return $this->belongsToMany(DailyTask::class, 'user_daily_tasks')->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // For development, allow everyone or check email
        // return $this->email === 'admin@noorly.com';
        return true;
    }
}
