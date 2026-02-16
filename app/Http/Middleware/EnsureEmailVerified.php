<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->email_verified_at) {
             return response()->json([
                 'status' => false,
                 'message' => 'Email is not verified.',
                 'data' => null,
                 'meta' => [
                     'lang' => app()->getLocale(),
                     'timestamp' => now()->toIso8601String(),
                 ]
             ], 403);
        }

        return $next($request);
    }
}
