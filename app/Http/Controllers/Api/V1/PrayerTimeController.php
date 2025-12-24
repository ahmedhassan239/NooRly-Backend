<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Prayers\Services\PrayerTimesService;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrayerTimeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PrayerTimesService $prayerService
    ) {}

    /**
     * Get prayer times for the given location and date.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'date' => 'nullable|date_format:Y-m-d',
            'method' => 'nullable|integer',
            'madhab' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        try {
            $data = $this->prayerService->getTimes($request->all());
            
            // Note: If cached, the 'cached' flag in meta might be stale
            // We can wrap the response to indicate if it was served from cache
            // but for simplicity, the service handles it.

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get Hijri date.
     */
    public function calendar(Request $request): JsonResponse
    {
        // For simplicity, we can fetch from AlAdhan as well or use a library
        // Returning a placeholder or implementing if needed
        return $this->successResponse([
            'hijri' => '1447-06-15', // Placeholder
            'gregorian' => now()->toDateString()
        ], "Coming soon");
    }
}
