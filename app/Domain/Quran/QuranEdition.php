<?php

namespace App\Domain\Quran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranEdition extends Model
{
    protected $primaryKey = 'identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'locale',
        'type',
        'format',
        'name',
        'english_name',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(QuranTranslation::class, 'edition_identifier', 'identifier');
    }
}
