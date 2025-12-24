<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Lessons\Services\LessonService;
use App\Domain\Lessons\Services\LessonDatasetService;
use App\Http\Resources\Api\V1\LessonResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LessonController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly LessonService $lessonService,
        private readonly LessonDatasetService $datasetService
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
            $lessons = $lessons->where('week_number', (int)$week);
        }

        if ($day) {
            $lessons = $lessons->where('day_number', (int)$day);
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
     * Get today's lesson.
     */
    public function today(Request $request): JsonResponse
    {
        $lang = app()->getLocale();
        $user = $request->user();
        
        $lesson = $this->lessonService->getTodayLesson($user, $lang);

        if (!$lesson) {
            return $this->errorResponse("Today's lesson not available", 404);
        }

        $lesson = $this->lessonService->enrichLessonWithState($user, $lesson);

        return $this->successResponse(new LessonResource($lesson));
    }

    /**
     * Get lesson detail.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $lang = app()->getLocale();
        $user = $request->user();
        
        $lesson = $this->datasetService->findById($id, $lang);

        if (!$lesson) {
            return $this->errorResponse("Lesson not found", 404);
        }

        $lesson = $this->lessonService->enrichLessonWithState($user, $lesson);

        return $this->successResponse(new LessonResource($lesson));
    }

    /**
     * Mark lesson as complete.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        // Find if lesson exists in dataset
        $lesson = $this->datasetService->findById($id);
        if (!$lesson) {
            return $this->errorResponse("Lesson not found", 404);
        }

        $this->lessonService->completeLesson($user, $id);

        return $this->successResponse(null, "Lesson marked as complete");
    }

    /**
     * Save/Update reflection.
     */
    public function reflection(Request $request, string $id): JsonResponse
    {
        $request->validate(['reflection_text' => 'required|string']);
        
        $user = $request->user();
        $lesson = $this->datasetService->findById($id);
        
        if (!$lesson) {
            return $this->errorResponse("Lesson not found", 404);
        }

        $this->lessonService->saveReflection($user, $id, $request->reflection_text);

        return $this->successResponse(null, "Reflection saved successfully");
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
