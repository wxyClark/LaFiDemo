<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 权限管理路由 (super.example.com)
|--------------------------------------------------------------------------
|
| 超级管理员后台的路由配置
|
*/

use Illuminate\Support\Facades\Route;

// 权限管理首页
Route::get('/', function () {
    return view('permission.index');
})->name('permission.index');

// 用户管理
Route::get('/users', function () {
    return view('permission.users');
})->name('permission.users');

// 角色管理
Route::get('/roles', function () {
    return view('permission.roles');
})->name('permission.roles');

// 权限管理
Route::get('/permissions', function () {
    return view('permission.permissions');
})->name('permission.permissions');

// 审计日志
Route::get('/audit-logs', function () {
    return view('permission.audit-logs');
})->name('permission.audit-logs');
