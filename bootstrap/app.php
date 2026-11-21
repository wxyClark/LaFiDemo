<?php

use App\Modules\Dev\Http\Middleware\AdminPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // 集成模块路由（laravel-filament 业务/API + laravel12 DEV）
            require __DIR__.'/../routes/laf/api.php';
            require __DIR__.'/../routes/laf/shop.php';
            require __DIR__.'/../routes/laf/user.php';
            require __DIR__.'/../routes/laf/xinfutong.php';
            require __DIR__.'/../routes/laf/open.php';
            require __DIR__.'/../routes/laf/public-address.php';
            require __DIR__.'/../routes/laf/permission.php';
            require __DIR__.'/../routes/dev.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.permission' => AdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
