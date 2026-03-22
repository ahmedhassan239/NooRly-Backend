<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Http\ExplicitMimeType;
use App\Support\Ramadan\RamadanIconRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves Ramadan guide icons through the API with CORS so Flutter Web (other origin)
 * can load them; static /public/assets files do not send CORS headers by default.
 */
class RamadanGuideIconController extends Controller
{
    public function show(string $filename): BinaryFileResponse
    {
        $basename = basename(rawurldecode($filename));
        $basename = str_replace(['/', '\\'], '', $basename);

        $allowed = array_column(RamadanIconRegistry::definitions(), 'filename');
        if (! in_array($basename, $allowed, true)) {
            abort(404);
        }

        $path = public_path('assets/ramadan/'.$basename);
        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => ExplicitMimeType::forBasename($basename),
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
