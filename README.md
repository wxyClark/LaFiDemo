# LaFiDemo

Laravel 13 + Filament 电商 SaaS（在线电商、O2O、分销）参考实现。

> 当前仓库是架构与工程规范的起点；功能按模块逐步实现。本文档不代表订单、支付、库存等业务功能已上线。

## 技术路线

- **运行时**：生产采用 PHP 8.4 最新安全补丁版；Composer 最低约束 PHP 8.4.3。PHP 8.3 不作为本项目部署基线，详见 [组件与运行时决策](docs/architecture-components.md)。
- **后端**：Laravel 13；模块化单体，按业务域组织代码。
- **管理后台**：与 Laravel 13 兼容的 Filament 5；平台运营后台与租户商家后台分离授权。
- **数据**：MySQL 8.4 LTS；Redis 用于缓存、队列和限流。库存正确性由数据库事务、约束和并发控制保障；金额使用最小货币单位整数及 Brick Money 精确计算。
- **异步任务**：Laravel Queue + Redis，生产环境使用 Horizon；订单、支付、库存等事件通过事务发件箱（outbox）可靠投递。
- **常用组件**：OpenSpout（流式 XLSX/CSV）、Laravel Dompdf（PDF）、Yansongda Pay（微信/支付宝支付适配）、chillerlan QR Code（二维码）、Intervention Image（图片处理）。按需加载，不将全部功能耦合进控制器。
- **API**：REST `/api/v1`、OpenAPI 文档；Laravel Sanctum 适合自有 App/前端，开放给第三方时再采用 OAuth 2.0。
- **前台**：先以独立 API 契约支持 Web / 小程序 / App；若建设独立 Web 商城，评估 Nuxt 3 或 Next.js，不把前台耦合到 Filament。
- **质量**：Pest、Laravel Pint、Larastan/PHPStan；CI 执行测试、静态分析、格式检查和迁移检查。

## 文档

- [开发规范总览](docs/rules/README.md)
- [技术选型：总览、对比与版本决策](doc/technology-selection.md)
- [系统架构图、模块目录与数据库表结构初稿](doc/architecture-blueprint.md)
- [组件与 PHP 运行时决策](docs/architecture-components.md)
- [架构决策、领域模型、约定、示例与实施路线](docs/architecture.md)
- [AI 辅助开发流程与提示词](docs/prompts/README.md)

## 核心原则

1. 先做模块化单体，不因“多租户/高并发”过早拆微服务；业务边界清楚、数据归属明确后再拆分。
2. 每个租户业务数据都显式归属 `tenant_id`，并在应用授权、查询范围、唯一索引和测试中验证租户隔离。
3. 订单商品、价格、收货信息等使用下单时快照；订单和支付状态用显式状态机，不允许任意改状态。
4. 付款、库存和退款流程要幂等、可审计、可补偿；第三方回调必须验签并防重放。
5. 用迁移、工厂、种子数据、自动化测试和可重复部署文档，保证仓库是可持续迭代的工程样例，而不只是演示代码。

## 当前状态

仓库包含架构、技术选型和开发规范文档，以及 Composer 依赖声明；完整 Laravel 应用骨架、迁移、业务模块和 `composer.lock` 仍需后续生成并通过依赖解析/CI 验证。
