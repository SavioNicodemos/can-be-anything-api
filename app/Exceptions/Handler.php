<?php

namespace App\Exceptions;

use App\Traits\ApiResponser;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponser;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (NotFoundHttpException $e) {
            return $this->errorResponse('Path not found.', $e->getStatusCode());
        });

        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return $this->errorResponse('Method not allowed for this route.', $e->getStatusCode());
        });

        $this->renderable(function (AccessDeniedHttpException $e) {
            return $this->errorResponse('This action is unauthorized.', $e->getStatusCode());
        });

        $this->renderable(function (FileNotFoundException $e) {
            return $this->errorResponse('File not found.', 404);
        });
    }
}
