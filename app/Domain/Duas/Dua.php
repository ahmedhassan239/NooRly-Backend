<?php

namespace App\Domain\Duas;


use Illuminate\Database\Eloquent\Model;

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


}
