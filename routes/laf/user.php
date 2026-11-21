<?php

declare(strict_types=1);

use App\Modules\Domains\User\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes — 用户管理 API
|--------------------------------------------------------------------------
|
| 需要 JWT 认证的用户管理接口
|
*/

Route::prefix('api/users')->middleware('auth:admin-api')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])
        ->name('api.users.index');

    Route::post('/', [CustomerController::class, 'store'])
        ->name('api.users.store');

    Route::get('/{customer}', [CustomerController::class, 'show'])
        ->name('api.users.show');

    Route::put('/{customer}', [CustomerController::class, 'update'])
        ->name('api.users.update');

    Route::delete('/{customer}', [CustomerController::class, 'destroy'])
        ->name('api.users.destroy');
});
