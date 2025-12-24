<?php

namespace App\Domain\Datasets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyContentSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'locale',
        'dua_id',
        'hadith_id',
        'payload',
    ];

    protected $casts = [
        'date' => 'date',
        'payload' => 'json',
    ];
}
