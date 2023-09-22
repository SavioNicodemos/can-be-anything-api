<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\UserService;
use App\Traits\ApiResponser;
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
        $userId = auth()->user()->id;

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
            'tel' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'avatar' => 'required|image',
        ]);

        $this->userService->create($request);

        return $this->successResponse(null, 201);
    }
}
