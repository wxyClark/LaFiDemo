# PHP、Laravel 与 Filament 开发规范

## 1. PHP 8.4+ 规范

### 编码与类型

- MUST 使用 `declare(strict_types=1);`（若项目初始化模板或框架生成文件另有统一约定，整仓保持一致并记录例外）。
- 新增类、方法、属性、常量尽可能声明类型；方法写清参数与返回类型。对外部输入在边界校验，不以 PHP 类型声明替代业务验证。
- 使用 PSR-12 风格并由 Laravel Pint 自动格式化；禁止手工引入与仓库 formatter 冲突的格式。
- 类、枚举、接口等使用 `StudlyCase`；方法/变量 `camelCase`；常量 `UPPER_SNAKE_CASE`；命名要表达业务语义。
- 使用 Enum / value object 表达有限状态、币种、履约类型等有业务意义的值；不为每个简单字符串机械抽象。状态与外部协议值转换集中处理。
- 金额不使用 `float`。使用整数最小货币单位与币种；涉及非两位小数币种时遵从 ISO 4217 精度，而不是假定所有币种均为“分”。可使用项目选定的 Brick Money 进行精确计算。
- 异常只用于异常情况；不要吞掉异常或 `catch (Throwable)` 后静默成功。捕获时应增加上下文、采取补偿/回滚并按策略重新抛出或转换为稳定错误。
- 避免隐式副作用、静态全局状态和难以测试的时间/随机数依赖；必要时通过 Clock、ID 生成接口等轻量边界注入。

### 依赖管理

- PHP 生产运行最低版本为 8.4.3，生产推荐 PHP 8.4 最新安全补丁版；详情见 [`../architecture-components.md`](../architecture-components.md)。提交 `composer.lock`；应用使用 `composer install`，不使用 `composer update` 作为部署步骤。
- 加依赖前确认维护状态、许可证、安全公告、PHP/Laravel/Filament 支持范围、传递依赖和替代方案。禁止提交 vendor 目录。
- Composer 脚本和自动加载遵循 Laravel Composer 结构；生产环境按部署要求使用优化自动加载和排除开发依赖。

## 2. Laravel 13 应用分层

### 请求处理边界

推荐路径：`Route -> Middleware -> FormRequest -> Controller/Filament Action -> Application Use Case -> Domain/Infrastructure -> Resource/Response`。

- Route 只声明路由、middleware、名称与版本；公共 API 置于 `/api/v1`，使用命名路由及一致的 throttle/auth middleware。
- Form Request 负责结构、格式、范围和场景校验；业务约束由应用用例/领域规则再次保障。不要在 Controller 中手工遍历并信任任意请求字段。
- Controller 负责协调请求/响应，不承载价格计算、库存扣减、佣金规则或跨模块事务流程。
- 使用 Laravel Policy/Gate 执行对象级授权。资源按允许字段白名单输出；禁止直接序列化带有密码、内部标记或租户敏感数据的完整模型。
- 依赖注入优先；配置通过 `config()` 读取，避免业务代码直接读取 `env()`。只在配置文件中读取环境变量。
- 事件/任务在事务提交后调度；涉及关键可靠交付的事件必须用 Outbox，不能把普通 after-commit dispatch 当作持久消息保证。

### 模块化单体

- 业务模块按 Tenancy、Catalog、Inventory、Cart、Checkout、Order、Payment、Promotion、Store、Distribution 划分；模块内部按复杂度采用 Domain/Application/Infrastructure/Http。
- 模块的公开应用接口是其他模块唯一写入入口。跨模块数据读取通过明确定义的查询接口/投影；禁止隐式依赖对方的内部模型、Observer 或表字段。
- 避免控制器、模型 Observer、Filament Hook 中隐藏关键业务副作用。关键流程显式调用用例并可独立测试。
- 不要为“架构完整”给每个 Eloquent 模型套通用 Repository；只有存在替换边界、复杂查询隔离或可验证测试价值时才引入。
- 单个事务保持边界清楚、尽量短。不要在事务/行锁内调用支付、物流、邮件等外部服务。

### Eloquent 与迁移

- 使用显式 `$fillable` 或 `$guarded` 策略防止批量赋值越权；租户 ID、状态、金额、支付结果不能从未经授权的输入直接 mass assign。
- 明确 eager loading、分页、查询列和排序；避免 N+1 与无界 `get()`。所有租户模型查询必须在可信租户上下文和授权范围内。
- 数据库约束是最后防线：唯一键、非空、外键和检查约束按目标 MySQL 能力定义。不能仅依赖表单验证保持不变量。
- Migration 应可重复从空库安装，命名清楚、按依赖顺序拆分；不要在迁移中依赖网络、真实业务数据或环境秘密。
- 生产结构修改遵循 expand-and-contract；数据修复写成可重跑、可观测的命令/任务，不将大量不确定数据变换塞入长时间阻塞迁移。

## 3. Filament 管理端

- 平台管理 Panel 与租户 Panel 明确区分认证入口、可访问资源、导航和授权策略。跨租户管理为显式高权限操作，需原因/审计，不能由普通租户管理员触发。
- Resource 页面可见性、导航隐藏、按钮禁用只是 UI 控制；真正的读写必须在 Policy、应用用例和查询范围执行权限检查。
- TenantContext 必须从已认证用户的有效 membership 得出；不能仅依赖 URL tenant slug 或前端 state。Filament 查询、Action、关系选择器、导出和批量操作都要租户隔离。
- Filament Action 仅负责收集输入与触发用例。订单状态、退款、库存调整等高风险动作使用明确确认、原因、权限校验、审计和幂等保护。
- 长耗时导出/批量处理排入队列；控制数据范围、任务执行者和租户上下文，避免越权导出。
- 表单字段明确可编辑范围、默认值、验证与脱敏；不允许直接编辑由系统计算或由支付回调确认的事实字段。
- 插件选型前核对 Laravel/Filament 主版本兼容、维护、许可和安全；避免未维护插件成为授权关键路径。

## 4. API 约定

- API 路由按 `/api/v1` 版本管理；新增字段尽量向后兼容。错误响应使用稳定机器可读错误码，避免泄露 SQL、堆栈、密钥或内部路径。
- 使用 Form Request 校验；分页、排序、筛选均有白名单和上限。禁止让客户端提供任意列名、排序表达式或无限 page size。
- 写 API 对下单、支付、退款等副作用实现幂等；幂等键限长、按租户/操作范围唯一，存储哈希，不记录原始敏感凭证。
- 认证与对象级授权分离：Sanctum 认证成功不代表可访问目标订单、门店或租户资源。
- 外部 Webhook 校验签名、时间窗、商户/账户、金额、币种、资源关联和事件唯一性；防重放，快速应答后异步处理时须先持久化事件。
- OpenAPI 文档描述认证、错误、分页、幂等行为和弃用策略；不在文档中放真实 token、客户数据或生产 URL 密钥。
- 对浏览器 SPA 使用框架推荐的 cookie/CSRF 方案；配置 CORS 精确白名单，不使用生产通配来源配凭据。

## 5. 命名与代码评审

- PHP 命名与 Laravel 默认习惯一致；数据库 snake_case 复数表名、snake_case 列名；API JSON 字段风格保持一致并以现有契约为准。
- 用业务术语命名 use case，例如 `PlaceOrder`、`ReserveInventory`、`ProcessPaymentWebhook`。避免含糊的 `CommonService`、`Helper` 聚合业务逻辑。
- PR 应说明业务影响、权限/租户边界、事务及幂等、迁移/回滚、测试结果；涉及 API 或表结构时附契约/数据模型更新。
