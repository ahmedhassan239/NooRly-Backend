<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Journey\Services\JourneyService;
use App\Domain\Lessons\Services\LessonDatasetService;
use App\Domain\Lessons\Services\LessonService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LessonResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LessonController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly LessonService $lessonService,
        private readonly LessonDatasetService $datasetService,
        private readonly JourneyService $journeyService
    ) {}

    /**
     * List lessons with unlock/completion status.
     */
    public function index(Request $request): JsonResponse
    {
        $lang = app()->getLocale();
        $week = $request->query('week');
        $day = $request->query('day');
        $perPage = $request->query('per_page', 20);
        $page = $request->query('page', 1);

        $lessons = collect($this->datasetService->getAll($lang));

        if ($week) {
            $lessons = $lessons->where('week_number', (int) $week);
        }

        if ($day) {
            $lessons = $lessons->where('day_number', (int) $day);
        }

        $user = $request->user();

        // Enrich with user state
        $lessons = $lessons->map(function ($lesson) use ($user) {
            return $this->lessonService->enrichLessonWithState($user, $lesson);
        });

        // Pagination
        $paginated = new LengthAwarePaginator(
            $lessons->forPage($page, $perPage),
            $lessons->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->successResponse(LessonResource::collection($paginated), null, 200, [
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'has_more' => $paginated->hasMorePages(),
        ]);
    }

    /**
     * Get today's lesson from Journey progress (current lesson = first non-completed).
     * Does not use LessonDatasetService. Never returns 500.
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $locale = $request->get('lang', app()->getLocale());

            $data = $this->journeyService->getCurrentLessonForUser($user, $locale);

            if ($data === null) {
                return $this->successResponse(null, 'No lesson today', 200);
            }

            return $this->successResponse($data, "Today's lesson retrieved", 200);
        } catch (\Throwable $e) {
            report($e);

            return $this->successResponse(null, 'No lesson today', 200);
        }
    }

    /**
     * Get lesson detail (resolves from DB when id is numeric, else dataset).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $lang = app()->getLocale();
        $user = $request->user();

        $lesson = $this->lessonService->findByIdForApi($id, $lang);

        if (! $lesson) {
            return $this->errorResponse('Lesson not found', 404);
        }

        $lesson = $this->lessonService->enrichLessonWithState($user, $lesson);

        return $this->successResponse(new LessonResource($lesson));
    }

    /**
     * Mark lesson as complete (DB or dataset lesson id).
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        if (! $this->lessonService->lessonExists($id)) {
            return $this->errorResponse('Lesson not found', 404);
        }

        $this->lessonService->completeLesson($request->user(), (string) $id);

        return $this->successResponse(null, 'Lesson marked as complete');
    }

    /**
     * Save/Update reflection (works for week_reflection type too).
     */
    public function reflection(Request $request, string $id): JsonResponse
    {
        $request->validate(['reflection_text' => 'required|string']);

        if (! $this->lessonService->lessonExists($id)) {
            return $this->errorResponse('Lesson not found', 404);
        }

        $this->lessonService->saveReflection($request->user(), (string) $id, $request->reflection_text);

        return $this->successResponse(null, 'Reflection saved successfully');
    }

    /**
     * Get user's lesson progress.
     */
    public function progress(Request $request): JsonResponse
    {
        $user = $request->user();
        $progress = $this->lessonService->getProgress($user);

        return $this->successResponse($progress);
    }
}
