<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 薪福通路由 (hr.example.com)
|--------------------------------------------------------------------------
|
| 薪福通系统的路由配置
|
*/

use Illuminate\Support\Facades\Route;

// 薪福通首页
Route::get('/', function () {
    return view('xinfutong.index');
})->name('xinfutong.index');

// 员工管理
Route::get('/employees', function () {
    return view('xinfutong.employees');
})->name('xinfutong.employees');

// 审批管理
Route::get('/approvals', function () {
    return view('xinfutong.approvals');
})->name('xinfutong.approvals');

// 同步日志
Route::get('/sync-logs', function () {
    return view('xinfutong.sync-logs');
})->name('xinfutong.sync-logs');
