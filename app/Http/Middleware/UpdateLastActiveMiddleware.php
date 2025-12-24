<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActiveMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only update if user is authenticated and response is successful
        if ($request->user('sanctum') && $response->getStatusCode() < 400) {
            $request->user('sanctum')->updateQuietly([
                'last_active_at' => now(),
            ]);
        }

        return $response;
    }
}
