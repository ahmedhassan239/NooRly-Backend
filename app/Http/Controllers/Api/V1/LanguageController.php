<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Languages\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    /**
     * Get all active languages.
     */
    public function index(): JsonResponse
    {
        $languages = Language::active()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'native_name', 'direction', 'is_default']);
        
        return response()->json([
            'data' => $languages,
        ]);
    }
}
