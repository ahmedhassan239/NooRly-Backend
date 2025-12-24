<?php

namespace App\Domain\Prayers\Services;

use App\Domain\Integrations\IntegrationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrayerTimesService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get prayer times for a specific location and date.
     */
    public function getTimes(array $params): array
    {
        $lat = $params['lat'] ?? null;
        $lng = $params['lng'] ?? null;
        $date = $params['date'] ?? now()->toDateString();
        $method = $params['method'] ?? 2; // ISNA
        $madhab = $params['madhab'] ?? 0; // Shafi/Hanafi
        
        $cacheKey = "p_times_{$lat}_{$lng}_{$date}_{$method}_{$madhab}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($lat, $lng, $date, $method, $madhab) {
            return $this->fetchFromProvider($lat, $lng, $date, $method, $madhab);
        });
    }

    /**
     * Fetch timings from AlAdhan API.
     */
    private function fetchFromProvider($lat, $lng, $date, $method, $madhab): array
    {
        $url = "http://api.aladhan.com/v1/timings/" . Carbon::parse($date)->format('d-m-Y');
        
        $queryParams = [
            'latitude' => $lat,
            'longitude' => $lng,
            'method' => $method,
            'school' => $madhab,
        ];

        $startTime = microtime(true);
        $response = Http::timeout(5)->get($url, $queryParams);
        $duration = microtime(true) - $startTime;

        // Log the integration call
        \App\Domain\Integrations\IntegrationLog::create([
            'provider' => 'AlAdhan',
            'endpoint' => $url,
            'status' => $response->successful() ? 'success' : 'fail',
            'http_code' => $response->status(),
            'duration_ms' => (int)($duration * 1000),
            'message' => $response->successful() ? null : $response->reason(),
        ]);

        if ($response->successful()) {
            $data = $response->json('data');
            return [
                'timings' => $data['timings'],
                'date' => $data['date'],
                'meta' => [
                    'method' => $data['meta']['method'],
                    'timezone' => $data['meta']['timezone'],
                    'provider' => 'AlAdhan',
                    'cached' => false,
                ]
            ];
        }

        throw new \Exception("Failed to fetch prayer times: " . $response->body());
    }
}
