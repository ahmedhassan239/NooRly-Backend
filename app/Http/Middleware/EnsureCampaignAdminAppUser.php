<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows AppUser API access to notification campaign admin routes when user id is listed in config.
 */
class EnsureCampaignAdminAppUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $allowed = config('noorly.campaign_admin_app_user_ids', []);
        if ($allowed === [] || ! in_array((int) $user->id, $allowed, true)) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
