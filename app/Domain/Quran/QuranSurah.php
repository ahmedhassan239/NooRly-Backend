<?php

namespace App\Domain\Quran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSurah extends Model
{
    protected $primaryKey = 'surah_number';
    public $incrementing = false;

    protected $fillable = [
        'surah_number',
        'name_ar',
        'name_en',
        'revelation_type',
        'ayahs_count',
    ];

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'surah_number', 'surah_number');
    }
}
