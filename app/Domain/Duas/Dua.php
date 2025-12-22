<?php

namespace App\Domain\Duas;

use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Dua extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, HasTranslations;

    protected static function newFactory()
    {
        return \Database\Factories\DuaFactory::new();
    }

    protected $fillable = ['title', 'arabic', 'translation', 'transliteration', 'category'];

    // HasTranslations implementation
    protected function getTranslationTable(): string
    {
        return 'dua_translations';
    }

    protected function getTranslationForeignKey(): string
    {
        return 'dua_id';
    }

    protected function getTranslatableFields(): array
    {
        return ['title', 'translation_text', 'transliteration', 'category'];
    }

    // Relationships
    public function translations()
    {
        return $this->hasMany(DuaTranslation::class);
    }
}
