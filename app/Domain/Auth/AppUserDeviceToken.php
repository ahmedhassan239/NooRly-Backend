<?php

namespace App\Domain\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUserDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'fcm_token',
        'platform',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function appUser()
    {
        return $this->belongsTo(AppUser::class);
    }
}
