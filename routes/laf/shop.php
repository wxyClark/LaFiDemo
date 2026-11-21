<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 商城前端路由 (shop.example.com)
|--------------------------------------------------------------------------
|
| 商城前台的路由配置
|
*/

use Illuminate\Support\Facades\Route;

// 商城首页
Route::get('/', function () {
    return view('shop.index');
})->name('shop.index');

// 商品列表
Route::get('/products', function () {
    return view('shop.products');
})->name('shop.products');

// 商品详情
Route::get('/products/{id}', function ($id) {
    return view('shop.product-detail', ['id' => $id]);
})->name('shop.product-detail');

// 购物车
Route::get('/cart', function () {
    return view('shop.cart');
})->name('shop.cart');

// 结算
Route::get('/checkout', function () {
    return view('shop.checkout');
})->name('shop.checkout');

// 订单
Route::get('/orders', function () {
    return view('shop.orders');
})->name('shop.orders');

// 用户中心
Route::get('/user/profile', function () {
    return view('shop.profile');
})->name('shop.profile');
