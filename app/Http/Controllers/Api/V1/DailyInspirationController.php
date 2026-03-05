<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DailyInspiration\DailyInspirationFromCollectionsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/daily-inspiration
 * Returns one random item from library collections (hadith or ayah only).
 * Item IDs come from HadithCollection / VerseCollection pivots; details from external DB.
 */
class DailyInspirationController extends Controller
{
    public function __invoke(Request $request, DailyInspirationFromCollectionsService $service): JsonResponse
    {
        $service->validateForceType($request);

        try {
            $result = $service->get($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }

        $response = [
            'status' => true,
            'message' => 'Daily inspiration retrieved successfully',
            'data' => $result['data'],
        ];
        if (isset($result['debug'])) {
            $response['debug'] = $result['debug'];
        }

        return response()->json($response);
    }
}
