<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\HelpNow\HelpCategory;
use App\Domain\HelpNow\HelpItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpNowController extends Controller
{
    /**
     * List all help categories with their active items (nested).
     * Returns localized fields based on Accept-Language.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->locale($request);

        $categories = HelpCategory::query()
            ->active()
            ->ordered()
            ->with(['items' => fn ($q) => $q->active()->ordered()])
            ->get()
            ->map(fn (HelpCategory $cat) => $cat->toApiArray($locale));

        return response()->json([
            'status' => true,
            'message' => 'Help categories and items retrieved successfully',
            'data' => $categories->values()->all(),
        ]);
    }

    /**
     * Get a single help item by slug (unique across all categories).
     */
    public function showItem(Request $request, string $slug): JsonResponse
    {
        $locale = $this->locale($request);

        $item = HelpItem::query()
            ->active()
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if (! $item) {
            return response()->json([
                'status' => false,
                'message' => 'Help item not found',
            ], 404);
        }

        $data = $item->toApiArray($locale);
        $data['category_title'] = $item->category?->getTitleForLocale($locale);

        return response()->json([
            'status' => true,
            'message' => 'Help item retrieved successfully',
            'data' => $data,
        ]);
    }

    private function locale(Request $request): string
    {
        $locale = $request->query('lang', $request->header('Accept-Language', 'en'));
        $locale = strlen($locale) >= 2 ? substr($locale, 0, 2) : 'en';

        return in_array($locale, ['ar', 'en'], true) ? $locale : 'en';
    }
}
