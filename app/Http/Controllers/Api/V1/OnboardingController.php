<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Onboarding\StartUserJourneyAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function store(Request $request, StartUserJourneyAction $action): JsonResponse
    {
        $validated = $request->validate([
            'shahada_date' => 'nullable|date',
            'goal' => 'required|string|max:255',
            'timezone' => 'required|string|timezone',
        ]);

        $action->execute($request->user(), $validated);

        return response()->json(['message' => 'Journey started successfully']);
    }
}
