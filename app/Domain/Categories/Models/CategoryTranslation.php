<?php

namespace App\Domain\Categories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CategoryTranslation Model
 * 
 * Represents a translation of a category in a specific language.
 * 
 * @property int $id
 * @property int $category_id
 * @property string $language_code
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Category $category
 */
class CategoryTranslation extends Model
{
    protected $table = 'category_translations';

    protected $fillable = [
        'category_id',
        'language_code',
        'name',
        'slug',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category this translation belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
