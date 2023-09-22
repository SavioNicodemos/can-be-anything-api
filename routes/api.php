<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewImageController;
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
    Route::post('/sessions', [AuthController::class, 'login'])->name('login.api');
    Route::post('/sessions/refresh-token', [AuthController::class, 'refreshToken'])->middleware(['throttle:5,1'])->name('login.api');

    Route::post('/users', [UserController::class, 'register'])->name('register.api');

    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->name('forgot.api');
    Route::put('/password/reset', [AuthController::class, 'passwordReset'])->name('forgot.api');

    Route::get('images/{imageName}', ViewImageController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/sessions', [AuthController::class, 'logout'])->name('logout.api');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout.api');

        Route::get('users/me', [UserController::class, 'me'])->name('users.me');

        Route::get('users/products', [ProductController::class, 'getMyProducts']);
        Route::post('products/images', [ProductController::class, 'addImages']);
        Route::delete('products/images', [ProductController::class, 'deleteImages']);

        Route::apiResource('products', ProductController::class);
    });
});
