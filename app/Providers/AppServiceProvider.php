<?php

namespace App\Providers;

use App\Modules\Catalog\Application\ProductPolicy;
use App\Modules\Catalog\Application\VariantPolicy;
use App\Modules\Catalog\Infrastructure\Product;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Inventory\Application\StockLedgerPolicy;
use App\Modules\Inventory\Application\StockPolicy;
use App\Modules\Inventory\Infrastructure\Stock;
use App\Modules\Inventory\Infrastructure\StockLedger;
use App\Modules\Tenancy\Application\CurrentTenant;
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
        // 请求作用域的当前租户上下文：必须单例（中间件写入、资源/用例读取
        // 同一实例；默认 auto-resolution 每次 make 新实例会丢失上下文）。
        $this->app->singleton(CurrentTenant::class);
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
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductVariant::class, VariantPolicy::class);
        Gate::policy(Stock::class, StockPolicy::class);
        Gate::policy(StockLedger::class, StockLedgerPolicy::class);
    }
}
