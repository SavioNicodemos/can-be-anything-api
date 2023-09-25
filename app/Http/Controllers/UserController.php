<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserController extends Controller
{
    use ApiResponser;

    public function __construct(protected UserService $userService, protected AuthService $authService)
    {
    }

    public function me(): JsonResponse
    {
        $userId = User::getLoggedUserId();

        return $this->successResponse($this->userService->getUserData($userId));
    }

    /**
     * @throws Throwable
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'confirmed|required|string|min:8',
            'avatar' => 'required|image',
        ]);

        $this->userService->create($request);

        return $this->successResponse(null, 201);
    }

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        try {
            $request->fulfill();

            return $this->successResponse(data: null, message: 'Email verified successfully');
        } catch (Exception) {
            return $this->errorResponse(message: 'Email verification failed');
        }
    }

    /**
     * @throws Exception
     */
    public function resendVerification(Request $request): JsonResponse
    {
        try {
            $this->userService->resendEmailVerification($request->user()->id);
            return $this->successResponse(data: null, message: 'Email verification link sent successfully');
        } catch (Throwable) {
            return $this->errorResponse(message: 'Email verification link sending failed');
        }
    }
}
