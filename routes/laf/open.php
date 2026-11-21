<?php

declare(strict_types=1);

use App\Modules\Domains\Address\Http\Controllers\AddressApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Open Routes — 无需认证
|--------------------------------------------------------------------------
|
| 不需要登录即可访问的公开接口
|
*/

Route::prefix('open')->middleware('throttle:60,1')->group(function () {

    // 地址公开 API
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressApiController::class, 'index']);
        Route::get('/children', [AddressApiController::class, 'children']);
        Route::get('/by-level/{level}', [AddressApiController::class, 'byLevel']);
        Route::get('/find/{code}', [AddressApiController::class, 'findByCode']);
        Route::get('/tree', [AddressApiController::class, 'tree']);
    });
});
