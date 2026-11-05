<?php

namespace App\Providers;

use App\Modules\Tenancy\Application\TenantPolicy;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 模块 Policy 显式注册（ADR-0001 c 条：模块自有资源的 Policy 放模块内；
        // Eloquent 自动解析约定 App\Models\* -> App\Policies\* 不适用于模块命名空间，
        // 故在组合根显式绑定）。基线默认全拒绝（TenantPolicy），待 RBAC/membership 落地放宽。
        Gate::policy(Tenant::class, TenantPolicy::class);
    }
}
