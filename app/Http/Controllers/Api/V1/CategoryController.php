<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Categories\Models\Category;
use App\Domain\ContentScopes\ContentScope;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * List categories, optionally filtered by scope.
     * 
     * Query parameters:
     * - scope: Scope key (e.g., "lessons", "duas") - filters categories by scope
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->with(['scope', 'translations']);

        // Filter by scope if provided
        if ($request->has('scope')) {
            $scopeKey = $request->input('scope');
            $scope = ContentScope::where('key', $scopeKey)->first();
            
            if (!$scope) {
                return $this->errorResponse("Scope '{$scopeKey}' not found", 404);
            }

            $query->byScope($scope->id);
        }

        $categories = $query->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'scope_id' => $category->scope_id,
                'scope' => $category->scope ? [
                    'id' => $category->scope->id,
                    'key' => $category->scope->key,
                    'label' => $category->scope->label,
                ] : null,
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'description' => $category->getDescription(),
                'translations' => $category->translations->map(function ($translation) {
                    return [
                        'language_code' => $translation->language_code,
                        'name' => $translation->name,
                        'slug' => $translation->slug,
                        'description' => $translation->description,
                    ];
                }),
            ];
        });

        return $this->successResponse($categories, 'Categories retrieved successfully');
    }

    /**
     * Get a single category by ID.
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::with(['scope', 'translations'])->find($id);

        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        $data = [
            'id' => $category->id,
            'scope_id' => $category->scope_id,
            'scope' => $category->scope ? [
                'id' => $category->scope->id,
                'key' => $category->scope->key,
                'label' => $category->scope->label,
            ] : null,
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
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
