<?php

namespace App\Domain\Progress;

use App\Domain\Users\User;
use Illuminate\Database\Eloquent\Model;

class DailyStreak extends Model
{
    protected $fillable = [
        'user_id',
        'current_streak',
        'max_streak',
        'last_activity_date',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
