<?php

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuranAyah extends Model
{
    protected $guarded = [];
    public $timestamps = false; // External DB usually static

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('content_sources.quran.connection', 'mysql_quran'));
        $this->setTable(config('content_sources.quran.table', 'quran.ayahs'));
    }

    public function surah()
    {
        return $this->belongsTo(QuranSurah::class, 'surah_id', 'id');
    }

    // Scopes using mapped column names
    public function scopeSearchArabic(Builder $query, string $term): Builder
    {
        $col = config('content_sources.quran.columns.text_ar', 'text');
        return $query->where($col, 'LIKE', "%{$term}%");
    }

    public function scopeSearchEnglish(Builder $query, string $term): Builder
    {
        // Placeholder until we map translations table
        return $query; 
    }
}
