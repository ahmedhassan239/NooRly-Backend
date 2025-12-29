<?php

namespace App\Domain\Quran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranAyah extends Model
{
    protected $fillable = [
        'surah_number',
        'ayah_number',
        'global_ayah_number',
        'text_ar',
    ];

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_number', 'surah_number');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuranTranslation::class, 'global_ayah_number', 'global_ayah_number');
    }
}
