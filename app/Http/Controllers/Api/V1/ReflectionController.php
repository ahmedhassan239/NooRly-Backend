<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Lessons\Services\LessonService;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReflectionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly LessonService $lessonService
    ) {}

    /**
     * GET /api/v1/reflections - List authenticated user's saved reflections (newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = $request->attributes->get('lang')
            ?? $request->query('lang')
            ?? app()->getLocale();
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'en';

        $reflections = $this->lessonService->getReflectionsForUser($user, $locale);

        return $this->successResponse($reflections);
    }
}
