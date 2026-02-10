<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SavedItemResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavedItemController extends Controller
{
    use ApiResponseTrait;

    /**
     * Supported item types for saving
     */
    private const SUPPORTED_TYPES = ['dua', 'hadith', 'lesson', 'verse', 'adhkar'];

    /**
     * List saved items of a specific type.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', Rule::in(self::SUPPORTED_TYPES)],
        ]);

        $user = $request->user();
        $query = $user->savedItems();

        if ($request->has('type')) {
            $query->where('item_type', $request->type);
        }

        $items = $query->latest()->paginate(20);

        return $this->successResponse(SavedItemResource::collection($items), null, 200, [
            'current_page' => $items->currentPage(),
            'total' => $items->total(),
            'has_more' => $items->hasMorePages(),
        ]);
    }

    /**
     * Save/Bookmark an item.
     */
    public function store(Request $request, string $type, string $itemId): JsonResponse
    {
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            return $this->errorResponse("Invalid item type. Supported types: " . implode(', ', self::SUPPORTED_TYPES), 400);
        }

        $user = $request->user();

        $savedItem = $user->savedItems()->updateOrCreate([
            'item_type' => $type,
            'item_id' => $itemId,
        ]);

        return $this->successResponse(new SavedItemResource($savedItem), "Item saved successfully", 201);
    }

    /**
     * Remove a saved item.
     */
    public function destroy(Request $request, string $type, string $itemId): JsonResponse
    {
        $user = $request->user();

        $deleted = $user->savedItems()
            ->where('item_type', $type)
            ->where('item_id', $itemId)
            ->delete();

        if (!$deleted) {
            return $this->errorResponse("Item not found in saved items", 404);
        }

        return $this->successResponse(null, "Item removed from saved successfully");
    }
}
