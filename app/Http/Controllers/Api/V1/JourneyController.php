<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Journey\Services\JourneyService;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly JourneyService $journeyService
    ) {}

    /**
     * GET /api/v1/journey - UI-ready journey with weeks and lessons.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = $request->get('lang', app()->getLocale());

        $data = $this->journeyService->getJourneyForUser($user, $locale);

        return $this->successResponse($data);
    }

    /**
     * GET /api/v1/journey/weeks/{week} - Lessons for one week.
     */
    public function week(Request $request, int $week): JsonResponse
    {
        $user = $request->user();
        $locale = $request->get('lang', app()->getLocale());

        $data = $this->journeyService->getWeekLessons($week, $user, $locale);

        if ($data === null) {
            return $this->errorResponse('Week not found', 404);
        }

        return $this->successResponse($data);
    }
}
