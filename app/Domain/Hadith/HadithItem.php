<?php

namespace App\Domain\Hadith;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithItem extends Model
{
    protected $fillable = [
        'collection_key',
        'book_number',
        'hadith_number',
        'grade',
        'reference',
        'text_ar',
        'text_en',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_key', 'collection_key');
    }
}
