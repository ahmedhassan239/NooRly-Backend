<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Lessons\CompleteLessonAction;
use App\Domain\Lessons\Lesson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lang = $request->attributes->get('lang', 'en');
        $searchTerm = $request->query('q');
        
        $query = Lesson::query()->withTranslation($lang);
        
        if ($searchTerm) {
            $query->searchTranslated($searchTerm);
        }
        
        $lessons = $query->orderByRaw('COALESCE(t_req.title, t_en.title) ASC')
                        ->paginate(20);
        
        return response()->json([
            'data' => LessonResource::collection($lessons),
            'meta' => [
                'lang' => $lang,
                'fallback_lang' => $request->attributes->get('fallback_lang'),
                'current_page' => $lessons->currentPage(),
                'total' => $lessons->total(),
            ]
        ]);
    }

    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        $lang = $request->attributes->get('lang', 'en');
        
        $lesson = Lesson::where('id', $lesson->id)
                       ->withTranslation($lang)
                       ->first();
        
        return response()->json([
            'data' => new LessonResource($lesson),
            'meta' => [
                'lang' => $lang,
                'fallback_lang' => $request->attributes->get('fallback_lang'),
            ]
        ]);
    }

    public function complete(Request $request, Lesson $lesson, CompleteLessonAction $action): JsonResponse
    {
        $action->execute($request->user(), $lesson);

        return response()->json(['message' => 'Lesson marked as complete']);
    }
}
