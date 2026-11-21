<?php

namespace App\Modules\Dev\Http\Middleware;

use App\Modules\Dev\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    /**
     * 管理员权限校验
     *
     * @author wxyClark
     *
     * @create 2025/11/29 11:32
     *
     * @version 1.0
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = Auth::id();

        $isAdmin = app(PermissionService::class)->getIsAdmin($userId);
        if (! $isAdmin) {
            return back()->withErrors(['permission' => '您没有权限访问此页面！']);
        }

        $request->offsetSet('user_id', $userId);

        return $next($request);
    }
}
