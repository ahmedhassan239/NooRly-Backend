<?php

namespace App\Domain\Journey;

use Illuminate\Database\Eloquent\Model;

class JourneyWeekTranslation extends Model
{
    protected $fillable = [
        'journey_week_id',
        'language_code',
        'title',
        'description',
    ];

    public function journeyWeek()
    {
        return $this->belongsTo(JourneyWeek::class);
    }
}
