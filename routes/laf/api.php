<?php

declare(strict_types=1);

use App\Modules\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — JWT Authentication
|--------------------------------------------------------------------------
|
| 前台调用的接口，使用 JWT Token 认证
| 公开路由：注册、登录
| 需认证路由：退出、刷新、个人信息
|
*/

Route::prefix('api')->group(function () {

    // 公开路由（无需认证）
    Route::post('/register', [AuthController::class, 'register'])
        ->name('api.register');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.login');

    // 需要 JWT 认证的路由
    Route::middleware('auth:admin-api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.logout');

        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('api.refresh');

        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.me');
    });
});
