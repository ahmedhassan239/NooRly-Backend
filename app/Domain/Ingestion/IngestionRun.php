<?php

namespace App\Domain\Ingestion;

use Illuminate\Database\Eloquent\Model;

class IngestionRun extends Model
{
    protected $fillable = [
        'job_name',
        'status',
        'started_at',
        'finished_at',
        'stats',
        'checkpoint',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'stats' => 'json',
        'checkpoint' => 'json',
    ];
}
