<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Check that required DB tables exist (for debugging / production verification).
     * GET /api/v1/health/tables
     */
    public function tables()
    {
        $required = ['duas', 'categories', 'categorizables', 'content_scopes'];
        $optional = ['dua_translations'];
        $tables = [];
        foreach ($required as $table) {
            $tables[$table] = Schema::hasTable($table);
        }
        foreach ($optional as $table) {
            $tables[$table] = Schema::hasTable($table);
        }
        $allRequiredExist = !in_array(false, array_intersect_key($tables, array_flip($required)));
        return $this->successResponse([
            'tables' => $tables,
            'duas_api_ready' => $allRequiredExist,
            'message' => $allRequiredExist
                ? 'Required tables exist. Duas API uses `duas` table only (no dua_translations required).'
                : 'Some required tables are missing. Check migrations.',
        ]);
    }
}
