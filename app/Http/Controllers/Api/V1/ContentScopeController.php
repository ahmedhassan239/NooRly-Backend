<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\ContentScopes\ContentScope;
use App\Http\Controllers\Controller;
use App\Observers\ContentScopeObserver;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentScopeController extends Controller
{
    use ApiResponseTrait;

    /**
     * List content scopes.
     * - context=library_tabs: only active + show_in_library_tabs, ordered by display_order, returns key, label, icon, color, display_order.
     * - otherwise: all active scopes (cached), feature_flag applied, key, label, icon, color.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->query('context') === 'library_tabs') {
            return $this->libraryTabs($request);
        }

        $scopes = Cache::remember(
            ContentScopeObserver::CACHE_KEY,
            ContentScopeObserver::CACHE_TTL_SECONDS,
            function () {
                return ContentScope::active()
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->get();
            }
        );

        $filtered = $scopes->filter(function ($scope) {
            if (empty($scope->feature_flag)) {
                return true;
            }
            return config("features.{$scope->feature_flag}", false) === true;
        })->values()->map(function ($scope) {
            return [
                'key' => $scope->key,
                'label' => $scope->label,
                'icon' => $scope->icon_key,
                'color' => $scope->icon_color,
                'icon_key' => $scope->icon_key,
                'icon_color' => $scope->icon_color,
                'iconKey' => $scope->icon_key,
                'iconColor' => $scope->icon_color,
            ];
        });

        return $this->successResponse($filtered->toArray(), 'Scopes retrieved successfully');
    }

    /**
     * Library tabs: active + show_in_library_tabs only, order by display_order.
     * Returns: key, label, icon, color, display_order.
     */
    private function libraryTabs(Request $request): JsonResponse
    {
        $scopes = ContentScope::query()
            ->where('is_active', true)
            ->where('show_in_library_tabs', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(function ($scope) {
                return [
                    'key' => $scope->key,
                    'label' => $scope->label,
                    'icon' => $scope->icon_key,
                    'color' => $scope->icon_color,
                    'display_order' => (int) $scope->display_order,
                ];
            });

        return $this->successResponse($scopes->toArray(), 'Library tabs retrieved successfully');
    }
}
