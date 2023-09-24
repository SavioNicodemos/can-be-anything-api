<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponser
{
    /**
     * Build success response
     */
    public function successResponse(mixed $data, int $code = Response::HTTP_OK, string $message = ''): JsonResponse
    {
        return $this->buildResponse(
            success: true,
            data: $data,
            code: $code,
            message: $message,
        );
    }

    private function buildResponse(
        bool $success = true,
        mixed $data = null,
        int $code = Response::HTTP_OK,
        string $message = ''
    ): JsonResponse {
        return response()->json(
            [
                'success' => $success,
                'message' => $message,
                'data' => $data,
            ],
            $code
        );
    }

    /**
     * Build error response
     */
    public function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return $this->buildResponse(
            success: false,
            data: null,
            code: $code,
            message: $message,
        );
    }
}
