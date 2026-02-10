<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\AppSettings\AppSetting;
use App\Domain\Home\HomeSection;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * App Configuration Controller
 * 
 * Provides remote configuration for the Flutter app.
 * All settings marked as "public" are exposed here.
 */
class AppConfigController extends Controller
{
    /**
     * Get app configuration
     * 
     * Returns all public app settings and home sections configuration.
     * This endpoint is used by the Flutter app to fetch remote config.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        
        // Get all public settings
        $settings = AppSetting::getPublicSettings();
        
        // Get home sections for the locale
        $homeSections = HomeSection::getForLocale($locale)
            ->map(fn ($section) => $section->toApiArray($locale))
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'App configuration retrieved successfully',
            'data' => [
                'settings' => $settings,
                'home_sections' => $homeSections,
                'locale' => $locale,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get a specific setting by key
     * 
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $setting = AppSetting::where('key', $key)
            ->where('is_public', true)
            ->first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Setting retrieved successfully',
            'data' => [
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
            ],
        ]);
    }

    /**
     * Get home sections only
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function homeSections(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        
        $sections = HomeSection::getForLocale($locale)
            ->map(fn ($section) => $section->toApiArray($locale))
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Home sections retrieved successfully',
            'data' => $sections,
        ]);
    }
}
