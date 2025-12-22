<?php

namespace App\Domain\Faq;

use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    use HasTranslations;
    protected $fillable = ['name', 'slug'];

    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    // HasTranslations implementation
    protected function getTranslationTable(): string
    {
        return 'faq_category_translations';
    }

    protected function getTranslationForeignKey(): string
    {
        return 'faq_category_id';
    }

    protected function getTranslatableFields(): array
    {
        return ['name'];
    }

    // Relationships
    public function translations()
    {
        return $this->hasMany(FaqCategoryTranslation::class);
    }
}
