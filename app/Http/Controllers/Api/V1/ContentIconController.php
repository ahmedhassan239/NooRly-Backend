<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Icons\PublicIconsRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves shared public/assets/icons with CORS for Flutter Web.
 */
class ContentIconController extends Controller
{
    public function show(string $filename): BinaryFileResponse
    {
        $basename = basename(rawurldecode($filename));
        $basename = str_replace(['/', '\\'], '', $basename);

        $allowed = array_column(PublicIconsRegistry::definitions(), 'filename');
        if (! in_array($basename, $allowed, true)) {
            abort(404);
        }

        $path = public_path('assets/icons/'.$basename);
        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
