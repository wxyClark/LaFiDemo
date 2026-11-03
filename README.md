# LaFiDemo

Laravel 13 + Filament 电商 SaaS（在线电商、O2O、分销）参考实现。

> 当前仓库是架构与工程规范的起点；功能按模块逐步实现。本文档不代表订单、支付、库存等业务功能已上线。

## 技术路线

- **后端**：Laravel 13、PHP 8.3+；模块化单体，按业务域组织代码。
- **管理后台**：Filament（选择与 Laravel 13 兼容的稳定版本），平台运营后台与租户商家后台分离授权。
- **数据**：MySQL 8.4 LTS；Redis 用于缓存、队列和限流。Redis 锁可用于协调，但库存正确性由数据库事务、约束和并发控制保障；所有金额用最小货币单位整数存储。
- **异步任务**：Laravel Queue + Redis，生产环境使用 Horizon；订单、支付、库存等事件通过事务发件箱（outbox）可靠投递。
- **文件**：S3 兼容对象存储；数据库只保存对象键和元数据。
- **API**：REST `/api/v1`、OpenAPI 文档；Laravel Sanctum 适合自有 App/前端，开放给第三方时再采用 OAuth 2.0。
- **前台**：先以独立 API 契约支持 Web / 小程序 / App；若建设独立 Web 商城，优先评估 Nuxt 3 或 Next.js，不把前台耦合到 Filament。
- **质量**：Pest、Laravel Pint、PHPStan/Larastan；CI 执行测试、静态分析、格式检查和迁移检查。

## 文档

- [技术选型：总览、对比与版本决策](doc/technology-selection.md)
- [系统架构图、模块目录与数据库表结构初稿](doc/architecture-blueprint.md)
- [架构决策、领域模型、约定、示例与实施路线](docs/architecture.md)

## 核心原则

1. 先做模块化单体，不因“多租户/高并发”过早拆微服务；业务边界清楚、数据归属明确后再拆分。
2. 每个租户业务数据都显式归属 `tenant_id`，并在应用授权、查询范围、唯一索引和测试中验证租户隔离。
3. 订单商品、价格、收货信息等使用下单时快照；订单和支付状态用显式状态机，不允许任意改状态。
4. 付款、库存和退款流程要幂等、可审计、可补偿；第三方回调必须验签并防重放。
5. 用迁移、工厂、种子数据、自动化测试和可重复部署文档，保证仓库是可持续迭代的工程样例，而不只是演示代码。

## 本地启动

项目骨架和依赖尚未加入。完成 Laravel 初始化后，按 `.env.example` 配置数据库和 Redis，再执行：

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
