<?php

namespace App\Domain\Integrations;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider',
        'endpoint',
        'status',
        'http_code',
        'duration_ms',
        'message',
        'payload_hash',
    ];
}
