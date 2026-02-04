<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\ContentScopes\ContentScope;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ContentScopeController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all active content scopes.
     */
    public function index(): JsonResponse
    {
        $scopes = ContentScope::active()
            ->orderBy('label')
            ->get()
            ->map(function ($scope) {
                return [
                    'id' => $scope->id,
                    'key' => $scope->key,
                    'label' => $scope->label,
                ];
            });

        return $this->successResponse($scopes, 'Scopes retrieved successfully');
    }
}
