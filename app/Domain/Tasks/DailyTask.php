<?php

namespace App\Domain\Tasks;

use App\Domain\Categories\Models\Category;
use App\Domain\Hadith\Models\HadithItem;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class DailyTask extends Model
{
    use HasFactory, HasTranslations;

    protected static function newFactory()
    {
        return \Database\Factories\DailyTaskFactory::new();
    }

    protected $fillable = [
        'day_number',
        'title',
        'type',
        'points',
    ];

    // HasTranslations implementation
    protected function getTranslationTable(): string
    {
        return 'daily_task_translations';
    }

    protected function getTranslationForeignKey(): string
    {
        return 'daily_task_id';
    }

    protected function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    // Relationships
    public function translations()
    {
        return $this->hasMany(DailyTaskTranslation::class);
    }

    /**
     * Get all categories for this daily task.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->withTimestamps();
    }

    /**
     * Get all Quran verses (Ayat) attached to this daily task.
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
     * Get all Hadith items attached to this daily task.
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
