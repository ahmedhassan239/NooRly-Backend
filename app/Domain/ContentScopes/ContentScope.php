<?php

namespace App\Domain\ContentScopes;

use App\Domain\Categories\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContentScope Model
 * 
 * Represents a dynamic scope for categorizing content.
 * Each scope defines a context where categories can be used (e.g., lessons, duas, daily_tasks).
 * 
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $model_class
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|Category[] $categories
 */
class ContentScope extends Model
{
    protected $table = 'content_scopes';

    protected $fillable = [
        'key',
        'label',
        'model_class',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all categories for this scope.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'scope_id');
    }

    /**
     * Scope to filter active scopes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the model class instance if valid.
     */
    public function getModelInstance()
    {
        if (!$this->model_class || !class_exists($this->model_class)) {
            return null;
        }

        return app($this->model_class);
    }
}
