<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    /**
     * Build success response
     */
    public function successResponse(mixed $data, int $statusCode = 200): JsonResponse
    {
        return response()->json($data, $statusCode);
    }

    /**
     * Build error response
     */
    public function errorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json(['message' => $message], $statusCode);
    }
}
