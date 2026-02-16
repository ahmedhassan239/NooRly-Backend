<?php

namespace App\Domain\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class AppUser extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'status',
        'last_active_at',
        'email_verified_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\AppUserFactory::new();
    }

    public function providers()
    {
        return $this->hasMany(AppUserProvider::class, 'app_user_id');
    }

    public function profile()
    {
        return $this->hasOne(AppUserProfile::class, 'app_user_id');
    }

    public function onboarding()
    {
        return $this->hasOne(\App\Domain\Users\AppUserOnboarding::class, 'app_user_id');
    }

    public function settings()
    {
        return $this->hasOne(\App\Domain\Users\AppUserSettings::class, 'app_user_id');
    }

    public function lessonCompletions()
    {
        return $this->hasMany(\App\Domain\Lessons\LessonCompletion::class, 'app_user_id');
    }

    public function lessonReflections()
    {
        return $this->hasMany(\App\Domain\Lessons\LessonReflection::class, 'app_user_id');
    }

    public function savedItems()
    {
        return $this->hasMany(\App\Domain\Users\SavedItem::class, 'app_user_id');
    }

    public function events()
    {
        return $this->hasMany(\App\Domain\Users\AppEvent::class, 'app_user_id');
    }

    public function progress()
    {
        return $this->hasMany(\App\Domain\Progress\UserProgress::class, 'app_user_id');
    }
}
