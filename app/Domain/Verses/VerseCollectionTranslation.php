<?php

namespace App\Domain\Verses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerseCollectionTranslation extends Model
{
    protected $table = 'verse_collection_translations';

    protected $fillable = [
        'verse_collection_id',
        'locale',
        'title',
        'description',
        'slug',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function verseCollection(): BelongsTo
    {
        return $this->belongsTo(VerseCollection::class, 'verse_collection_id');
    }
}
