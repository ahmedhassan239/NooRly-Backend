<?php

namespace App\Support\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @param array $meta
     * @return JsonResponse
     */
    protected function successResponse(mixed $data, ?string $message = null, int $code = 200, array $meta = []): JsonResponse
    {
        $response = [
            'data' => $data,
            'meta' => array_merge([
                'lang' => app()->getLocale(),
                'timestamp' => now()->toIso8601String(),
            ], $meta),
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'data' => null,
            'meta' => [
                'lang' => app()->getLocale(),
                'timestamp' => now()->toIso8601String(),
                'errors' => $errors,
            ],
            'message' => $message,
        ];

        return response()->json($response, $code);
    }
}
