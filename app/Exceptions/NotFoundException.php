<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class NotFoundException extends Exception
{
    protected string $modelName;

    public function __construct($modelName)
    {
        $this->modelName = $modelName ?: 'Data';
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => ucfirst($this->modelName).' not found.'], 404);
    }
}
