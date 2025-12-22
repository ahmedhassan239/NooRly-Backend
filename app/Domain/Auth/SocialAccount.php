<?php

namespace App\Domain\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\SocialAccountFactory::new();
    }

    protected $fillable = [
        'app_user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }

    public function appUser()
    {
        return $this->belongsTo(AppUser::class);
    }
}
