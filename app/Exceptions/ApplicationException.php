<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApplicationException extends Exception
{
    public function __construct(protected $message, protected $code = 400)
    {
        parent::__construct($message, $code);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => ucfirst($this->message)], $this->code);
    }
}
