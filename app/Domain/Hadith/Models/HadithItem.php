<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HadithItem extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('content_sources.hadith.connection', 'mysql_hadith'));
        $this->setTable(config('content_sources.hadith.table', 'all_hadiths_clean.hadiths'));
    }

    public function scopeSearchArabic(Builder $query, string $term): Builder
    {
        $col = config('content_sources.hadith.columns.text_ar', 'text_ar');
        return $query->where($col, 'LIKE', "%{$term}%");
    }

    public function scopeSearchEnglish(Builder $query, string $term): Builder
    {
        $col = config('content_sources.hadith.columns.text_en', 'text_en');
        return $query->where($col, 'LIKE', "%{$term}%");
    }
}
