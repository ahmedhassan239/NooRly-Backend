<?php

namespace App\Domain\Users;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class AppUserOnboarding extends Model
{
    protected $table = 'app_user_onboarding';

    protected $fillable = [
        'app_user_id',
        'start_date',
        'shahada_date',
        'learning_goal',
        'timezone',
    ];

    protected $casts = [
        'start_date' => 'date',
        'shahada_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
