<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Tasks\CompleteDailyTaskAction;
use App\Domain\Tasks\DailyTask;
use App\Http\Controllers\Controller;
use App\Http\Resources\DailyTaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyTaskController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentDay = $user->current_day ?? 1;
        $lang = $request->attributes->get('lang', 'en');
        
        $tasks = DailyTask::where('day_number', $currentDay)
                         ->withTranslation($lang)
                         ->get();
        
        return response()->json([
            'data' => DailyTaskResource::collection($tasks),
            'meta' => [
                'lang' => $lang,
                'fallback_lang' => $request->attributes->get('fallback_lang'),
            ]
        ]);
    }

    public function complete(Request $request, DailyTask $dailyTask, CompleteDailyTaskAction $action): JsonResponse
    {
        $action->execute($request->user(), $dailyTask);
        
        return response()->json(['message' => 'Task marked as complete']);
    }
}
