# LaFiDemo

Laravel 13 + Filament 电商 SaaS（在线电商、O2O、分销）参考实现。

> 当前仓库以架构和工程规范为起点；功能按模块逐步实现。本文档不代表订单、支付、库存等业务功能已上线。

## 技术路线

- **PHP**：生产采用 PHP 8.4 最新安全补丁版；Composer 最低约束 PHP 8.4.3。PHP 8.3 不作为本项目部署基线，详见 [组件与运行时决策](docs/architecture/architecture-components.md)。
- **后端**：Laravel 13，模块化单体，按业务域组织代码。
- **管理后台**：与 Laravel 13 兼容的 Filament 5；平台运营后台与租户商家后台分离授权。
- **数据**：MySQL 8.4 LTS；Redis 用于缓存、队列和限流。库存正确性由数据库事务、约束和并发控制保障；金额以最小货币单位整数持久化，使用 Brick Money 等精确计算策略。
- **异步任务**：Laravel Queue + Redis，生产环境使用 Horizon；订单、支付、库存等关键事件通过事务 Outbox 可靠投递。
- **基础组件**：OpenSpout（流式 XLSX/CSV）、Laravel Dompdf（PDF）、Yansongda Pay（微信/支付宝适配）、chillerlan QR Code（二維码）、Intervention Image（图片处理）。使用范围、风险和版本约束见组件决策文档。
- **API**：REST `/api/v1`、OpenAPI；Sanctum 适用于自有客户端，开放第三方授权时再评估 OAuth 2.0。
- **质量**：Pest、Laravel Pint、Larastan/PHPStan；CI 检查测试、静态分析、格式和迁移。

## 文档

- [开发规范总览](docs/rules/README.md)
- [技术选型：总览、对比与版本决策](docs/technology-selection.md)
- [系统架构图、模块目录与数据库表结构初稿](docs/architecture-blueprint.md)
- [电商平台总体方案与实施蓝图](docs/architecture/ecommerce-platform-blueprint.md)
- [组件与 PHP 运行时决策](docs/architecture/architecture-components.md)
- [架构决策、领域模型、约定、示例与实施路线](docs/architecture/architecture.md)
- [AI 辅助开发流程与提示词](docs/prompts/README.md)

## 核心原则

1. 先采用模块化单体，不因“多租户/高并发”过早拆微服务。
2. 每个租户业务数据都显式归属 `tenant_id`，并通过授权、查询范围、唯一索引和测试验证租户隔离。
3. 订单商品、价格、地址等保存下单快照；订单、支付、履约状态通过显式状态机转换。
4. 订单、库存、支付、退款和分销账务操作必须幂等、可审计、可补偿；第三方通知必须验签并防重放。
5. 用迁移、工厂、种子数据、自动化测试和可重复部署文档，形成可持续迭代的工程样例。

## 当前状态

仓库包含架构、选型和开发规范文档，以及 Composer 依赖声明；完整 Laravel 应用骨架、迁移、业务模块和 `composer.lock` 仍需生成并通过依赖解析/CI 验证。
