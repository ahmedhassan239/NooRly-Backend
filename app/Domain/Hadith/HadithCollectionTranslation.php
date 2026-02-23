<?php

namespace App\Domain\Hadith;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithCollectionTranslation extends Model
{
    protected $table = 'library_hadith_collection_translations';

    protected $fillable = [
        'hadith_collection_id',
        'locale',
        'title',
        'description',
        'slug',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function hadithCollection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'hadith_collection_id');
    }
}
