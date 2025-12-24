<?php

namespace App\Domain\Users;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;

class SavedItem extends Model
{
    protected $table = 'saved_items';

    protected $fillable = [
        'app_user_id',
        'item_type',
        'item_id',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
