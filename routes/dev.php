<?php

use App\Modules\Dev\Http\Controllers\DevController;
use App\Modules\Dev\Http\Controllers\QueryController;
use Illuminate\Support\Facades\Route;

// DEV 查询/导出（来自 laravel12 自定义模块）
Route::middleware(['auth', 'admin.permission'])->group(function () {
    Route::get('/dev', [DevController::class, 'query'])->name('dev');
    Route::post('/query/list', [QueryController::class, 'list'])->name('query.list')->middleware('throttle:1000,1');
    Route::post('/query/export', [QueryController::class, 'export'])->name('query.export')->middleware('throttle:1000,1');
});
