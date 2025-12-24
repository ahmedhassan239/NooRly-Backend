<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;

class SystemController extends Controller
{
    use ApiResponseTrait;

    /**
     * System health check.
     */
    public function health()
    {
        return $this->successResponse([
            'status' => 'ok',
            'version' => '1.0.0',
            'laravel_version' => app()->version(),
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
