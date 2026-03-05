<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Journey\Services\JourneyService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JourneySummaryResource;
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

    /**
     * GET /api/v1/journey/summary - Compact journey profile summary for Profile screen.
     * Locale-aware; never returns 500 (uses safe defaults on failure).
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $locale = $request->get('lang', app()->getLocale());

            $data = $this->journeyService->getSummaryForUser($user, $locale);

            return $this->successResponse(new JourneySummaryResource($data), 'Journey summary retrieved');
        } catch (\Throwable $e) {
            report($e);

            $default = [
                'day_index' => 1,
                'total_days' => 90,
                'streak_days' => 0,
                'active_weeks' => 0,
                'left_days' => 89,
                'completed_lessons' => 0,
                'total_lessons' => 90,
                'completion_percent' => 0.0,
                'milestones' => [],
                'current_lesson' => null,
            ];

            return $this->successResponse(new JourneySummaryResource($default), 'Journey summary retrieved');
        }
    }
}
