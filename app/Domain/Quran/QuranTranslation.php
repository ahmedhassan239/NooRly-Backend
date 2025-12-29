<?php

namespace App\Domain\Quran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranTranslation extends Model
{
    protected $fillable = [
        'global_ayah_number',
        'locale',
        'edition_identifier',
        'translator_name',
        'text',
    ];

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'global_ayah_number', 'global_ayah_number');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(QuranEdition::class, 'edition_identifier', 'identifier');
    }
}
