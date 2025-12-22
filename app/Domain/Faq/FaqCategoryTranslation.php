<?php

namespace App\Domain\Faq;

use Illuminate\Database\Eloquent\Model;

class FaqCategoryTranslation extends Model
{
    protected $fillable = [
        'faq_category_id',
        'language_code',
        'name',
    ];

    public function faqCategory()
    {
        return $this->belongsTo(FaqCategory::class);
    }
}
