<?php

namespace App\Domain\Faq;

use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasTranslations;
    protected $fillable = ['faq_category_id', 'question', 'answer'];

    public function category()
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    // HasTranslations implementation
    protected function getTranslationTable(): string
    {
        return 'faq_translations';
    }

    protected function getTranslationForeignKey(): string
    {
        return 'faq_id';
    }

    protected function getTranslatableFields(): array
    {
        return ['question', 'answer'];
    }

    // Relationships
    public function translations()
    {
        return $this->hasMany(FaqTranslation::class);
    }
}
