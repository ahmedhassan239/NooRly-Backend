<?php

namespace App\Domain\Tasks;

use Illuminate\Database\Eloquent\Model;

class DailyTaskTranslation extends Model
{
    protected $fillable = [
        'daily_task_id',
        'language_code',
        'title',
        'description',
    ];

    public function dailyTask()
    {
        return $this->belongsTo(DailyTask::class);
    }
}
