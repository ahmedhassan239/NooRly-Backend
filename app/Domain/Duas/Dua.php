<?php

namespace App\Domain\Duas;

use App\Domain\Categories\Models\Category;
use App\Domain\Hadith\Models\HadithItem;
use App\Domain\QuranAllLang\Models\QuranVerse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Dua extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\DuaFactory::new();
    }

    protected $fillable = [
        'dua_key',
        'category_key',
        'source',
        'text_ar',
        'transliteration',
        'text_en',
        'meta',
    ];

    /**
     * Get all categories for this dua.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->withTimestamps();
    }

    /**
     * Get all Quran verses (Ayat) attached to this dua.
     */
    public function quranAyahs(): MorphToMany
    {
        return $this->morphToMany(
            QuranVerse::class,
            'ayahable',
            'quran_ayahables',
            'ayahable_id',
            'quran_ayah_id'
        )->withTimestamps();
    }

    /**
     * Get all Hadith items attached to this dua.
     */
    public function hadithItems(): MorphToMany
    {
        return $this->morphToMany(
            HadithItem::class,
            'hadithable',
            'hadith_itemables',
            'hadithable_id',
            'hadith_item_id'
        )->withTimestamps();
    }
}
