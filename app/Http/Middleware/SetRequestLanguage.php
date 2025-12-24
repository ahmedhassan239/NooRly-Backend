<?php

namespace App\Http\Middleware;

use App\Domain\Languages\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetRequestLanguage
{
    /**
     * Handle an incoming request and resolve language.
     *
     * Priority:
     * 1. Query param ?lang=xx
     * 2. Accept-Language header
     * 3. X-Lang header
     * 4. Default (en)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestedLang = $this->resolveLanguage($request);
        $language = Language::resolve($requestedLang);
        
        // Store resolved language in request
        $request->attributes->set('lang', $language->code);
        $request->attributes->set('language', $language);
        
        // Track if fallback was used
        if ($requestedLang && $requestedLang !== $language->code) {
            $request->attributes->set('fallback_lang', $language->code);
        } else {
            $request->attributes->set('fallback_lang', null);
        }
        
        // Set Laravel locale
        app()->setLocale($language->code);
        
        return $next($request);
    }

    /**
     * Resolve requested language from request.
     */
    private function resolveLanguage(Request $request): ?string
    {
        // 1. Query parameter
        if ($lang = $request->query('lang')) {
            return $lang;
        }
        
        // 2. Accept-Language header
        if ($acceptLang = $request->header('Accept-Language')) {
            // Parse Accept-Language (e.g., "en-US,en;q=0.9,ar;q=0.8")
            $lang = $this->parseAcceptLanguage($acceptLang);
            if ($lang) {
                return $lang;
            }
        }
        
        // 3. X-Lang header
        if ($xLang = $request->header('X-Lang')) {
            return $xLang;
        }
        
        // 4. Default
        return 'en';
    }

    /**
     * Parse Accept-Language header and extract primary language code.
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        // Split by comma and get first language
        $languages = explode(',', $header);
        if (empty($languages)) {
            return null;
        }
        
        // Get first language (highest priority)
        $primaryLang = trim(explode(';', $languages[0])[0]);
        
        // Extract language code (e.g., "en-US" -> "en")
        return strtolower(explode('-', $primaryLang)[0]);
    }
}
