<?php

namespace App\Domain\Hadith;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HadithCollection extends Model
{
    protected $primaryKey = 'collection_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'collection_key',
        'name_ar',
        'name_en',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HadithItem::class, 'collection_key', 'collection_key');
    }
}
