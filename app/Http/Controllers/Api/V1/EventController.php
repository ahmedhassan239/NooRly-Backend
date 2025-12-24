<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    use ApiResponseTrait;

    /**
     * Log a new event.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string|max:255',
            'entity_type' => 'required|string|max:255',
            'entity_id' => 'required|string|max:255',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        $user = $request->user('sanctum');

        $event = \App\Domain\Users\AppEvent::create([
            'app_user_id' => $user?->id,
            'event_type' => $request->event_type,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'meta' => $request->meta,
        ]);

        return $this->successResponse(new EventResource($event), "Event logged successfully", 201);
    }
}
