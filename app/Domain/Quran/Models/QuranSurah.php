<?php

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;

class QuranSurah extends Model
{
    protected $connection = 'mysql_quran';
    protected $table = 'quran.surahs'; // Assumed from previous context, safer to qualify
    
    // Explicitly define if possible, or leave generalized
    protected $guarded = [];
}
