<?php

namespace App\Domain\Duas;

use Illuminate\Database\Eloquent\Model;

class DuaTranslation extends Model
{
    protected $fillable = [
        'dua_id',
        'language_code',
        'title',
        'translation_text',
        'transliteration',
        'category',
    ];

    public function dua()
    {
        return $this->belongsTo(Dua::class);
    }
}
