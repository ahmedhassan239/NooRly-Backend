<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseHealthService
{
    public function checkConnection(string $connection): array
    {
        try {
            DB::connection($connection)->getPdo();
            $lat = $this->measureLatency($connection);
            return [
                'status' => 'ok',
                'latency' => $lat,
                'message' => 'Connected successfully',
                'database' => DB::connection($connection)->getDatabaseName(),
            ];
        } catch (\Exception $e) {
            Log::error("DB Connection failed: $connection", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'latency' => 0,
                'message' => $e->getMessage(),
                'database' => 'Unknown',
            ];
        }
    }

    private function measureLatency(string $connection): float
    {
        $start = microtime(true);
        DB::connection($connection)->select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }
}
