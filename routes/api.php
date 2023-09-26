<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewImageController;
use App\Http\Controllers\WishListController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'v1'], function () {
    // Not authenticated routes
    Route::post('/sessions', [AuthController::class, 'login'])->name('login');
    Route::post('/sessions/refresh-token', [AuthController::class, 'refreshToken'])
        ->middleware(['throttle:5,1'])
        ->name('refresh-token');

    Route::post('/users', [UserController::class, 'register'])->name('register-user');

    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::put('/password/reset', [AuthController::class, 'passwordReset'])->name('reset-password');

    Route::get('images/{imageName}', ViewImageController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/sessions', [AuthController::class, 'logout'])->name('logout');

        Route::get('users/me', [UserController::class, 'me'])->name('users.me');

        Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])
            ->middleware(['signed'])->name('verification.verify');
        Route::post('/email/resend-verification', [UserController::class, 'resendVerification'])
            ->middleware(['throttle:6,1'])->name('verification.send');

        Route::get('users/products', [ProductController::class, 'getMyProducts']);
        Route::patch('products/{productId}/images', [ProductController::class, 'changeImages']);
        Route::patch('products/{productId}/is-active', [ProductController::class, 'toggleActive']);

        Route::apiResource('products', ProductController::class)->except('index');

        Route::get('users/{username}/wish-lists', [WishListController::class, 'index']);
        Route::apiResource('wish-lists', WishListController::class)->except('index');
    });
});
