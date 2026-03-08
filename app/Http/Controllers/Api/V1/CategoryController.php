<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Adhkar\Adhkar;
use App\Domain\Categories\Models\Category;
use App\Domain\ContentScopes\ContentScope;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    /** Allowed scope keys for filtering categories. */
    private const ALLOWED_SCOPE_KEYS = ['adhkar', 'duas', 'hadith', 'verses', 'lesson', 'lessons'];

    /**
     * List categories, optionally filtered by scope.
     *
     * GET /api/v1/categories?scope=adhkar
     * Returns only categories where category.scope_id matches the scope (e.g. adhkar).
     *
     * Query parameters:
     * - scope: Scope key (adhkar, duas, hadith, verses, lesson) - required for filtering
     *
     * When scope=adhkar, response includes items_count (adhkar count).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'scope' => ['sometimes', 'string', 'in:' . implode(',', self::ALLOWED_SCOPE_KEYS)],
        ]);

        $query = Category::query()->with(['scope', 'translations']);

        $scopeKey = $request->input('scope');
        if ($request->has('scope') && $scopeKey !== null && $scopeKey !== '') {
            $scope = ContentScope::where('key', $scopeKey)->first();
            if (!$scope) {
                return $this->errorResponse("Scope '{$scopeKey}' not found", 404);
            }
            $query->byScope($scope->id);
        }

        $locale = $this->preferredLocale($request);

        $categories = $query->get()->map(function ($category) use ($scopeKey, $locale) {
            $item = [
                'id' => $category->id,
                'scope_id' => $category->scope_id,
                'scope' => $category->scope ? [
                    'id' => $category->scope->id,
                    'key' => $category->scope->key,
                    'label' => $category->scope->label,
                ] : null,
                'name' => $category->getName($locale),
                'slug' => $category->getSlug($locale),
                'description' => $category->getDescription($locale),
                'icon' => filled($category->icon_key) ? $category->icon_key : null,
                'translations' => $category->translations->map(function ($translation) {
                    return [
                        'language_code' => $translation->language_code,
                        'name' => $translation->name,
                        'slug' => $translation->slug,
                        'description' => $translation->description,
                    ];
                }),
            ];

            if ($scopeKey === 'adhkar') {
                $item['items_count'] = Adhkar::where('category_id', $category->id)->active()->count();
            }

            return $item;
        });

        return $this->successResponse($categories, 'Categories retrieved successfully');
    }

    private function preferredLocale(Request $request): string
    {
        $header = $request->header('Accept-Language', 'en');
        $first = trim(explode(',', $header)[0] ?? 'en');
        $lang = trim(explode(';', $first)[0] ?? 'en');
        $locale = strlen($lang) >= 2 ? strtolower(substr($lang, 0, 2)) : 'en';
        return in_array($locale, ['en', 'ar'], true) ? $locale : 'en';
    }

    /**
     * Get a single category by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $category = Category::with(['scope', 'translations'])->find($id);

        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        $locale = $this->preferredLocale($request);
        $data = [
            'id' => $category->id,
            'scope_id' => $category->scope_id,
            'scope' => $category->scope ? [
                'id' => $category->scope->id,
                'key' => $category->scope->key,
                'label' => $category->scope->label,
            ] : null,
            'name' => $category->getName($locale),
            'slug' => $category->getSlug($locale),
            'description' => $category->getDescription($locale),
            'icon' => filled($category->icon_key) ? $category->icon_key : null,
            'translations' => $category->translations->map(function ($translation) {
                return [
                    'language_code' => $translation->language_code,
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                ];
            }),
        ];

        return $this->successResponse($data, 'Category retrieved successfully');
    }
}
