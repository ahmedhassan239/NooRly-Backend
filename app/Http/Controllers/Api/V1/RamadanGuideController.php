<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\RamadanGuide\RamadanGuideItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RamadanGuideController extends Controller
{
    /**
     * List active Ramadan guide items ordered by sort_order.
     * Returns localized title, description, content based on Accept-Language.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->locale($request);

        $items = RamadanGuideItem::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (RamadanGuideItem $item) => $item->toApiArray($locale));

        return response()->json([
            'status' => true,
            'message' => 'Ramadan guide items retrieved successfully',
            'data' => $items->values()->all(),
        ]);
    }

    /**
     * Get a single Ramadan guide item by slug.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->locale($request);

        $item = RamadanGuideItem::query()
            ->active()
            ->where('slug', $slug)
            ->first();

        if (! $item) {
            return response()->json([
                'status' => false,
                'message' => 'Ramadan guide item not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ramadan guide item retrieved successfully',
            'data' => $item->toApiArray($locale),
        ]);
    }

    private function locale(Request $request): string
    {
        $locale = $request->query('lang', $request->header('Accept-Language', 'en'));
        $locale = strlen($locale) >= 2 ? substr($locale, 0, 2) : 'en';

        return in_array($locale, ['ar', 'en'], true) ? $locale : 'en';
    }
}
