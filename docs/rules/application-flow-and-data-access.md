# 应用调用链路、共享代码与数据访问规范

本文补充 [`coding-standards.md`](coding-standards.md) 中的 Laravel 分层约定，适用于 API、Filament、队列消费者和 Artisan 定时任务。分层服务于依赖方向和业务边界，不要求每个简单 CRUD 都机械地产生一套类。

## 1. 标准调用链路

HTTP 写请求的推荐路径：

```text
Route
  -> Middleware（认证、可信租户上下文、限流等）
  -> FormRequest（输入结构/格式校验、授权入口协调）
  -> Controller / Filament Action（薄入口）
  -> Application Use Case / Service（业务用例与事务协调）
  -> Repository（复杂或值得隔离的持久化查询/写入）
  -> Eloquent Model / MySQL
  -> Resource / Presenter（按边界转换输出）
```

- Route 只声明路由、middleware、名称和版本；租户解析及访问控制必须由可信的认证成员关系和服务端上下文完成，不能信任客户端传入的 `tenant_id`。
- Form Request 负责 HTTP 输入的结构、格式、范围和场景校验，并可协调 Policy 授权；它不是业务规则的唯一防线。Application 用例必须再次保证核心业务不变量，避免任务、命令等非 HTTP 调用绕过规则。
- Controller / Filament Action 负责取已验证输入、取得可信上下文、调用一个或少量明确的用例，并将结果交给输出层。不得在入口复制业务逻辑、直接编排多表事务或调用外部渠道完成核心流程。
- Application 层表达用例和事务边界；Domain 层承载领域规则和值对象；Infrastructure 层实现数据库、队列和外部适配器。Repository（如确有价值）属于持久化边界，不应把业务决策藏在查询方法中。
- 允许为读场景采用专门的 Query/Read Model，不必强制所有读取经过通用 Repository；跨模块只能调用对方公开的 Application 接口或只读查询接口，禁止直接依赖对方内部 Model/表。
- Filament、API、Job 与 Artisan Command 是不同入口，应复用同一 Application 用例，不得互相调用 Controller、FormRequest 或 Filament Resource。

## 2. 输出格式转换是否单独建文件

当输出格式、字段白名单或协议具有独立语义时，应单独定义转换类：

- HTTP API 使用 Laravel `JsonResource`（按模块放在 `Http/Resources/`），统一控制可见字段、关系和版本化契约；不得直接返回或序列化完整 Eloquent Model。
- 输入/输出跨层传递有稳定结构、复杂映射或需要强类型约束时，定义专用 DTO / Data 对象，放在相应模块的 `Application/DTO` 或 `Application/Data`，并避免依赖 Request、Response、Model 等传输/基础设施对象。
- Filament 展示转换若只是简单字段映射可留在 Resource/Page；若转换规则复杂且有复用或测试价值，提取模块内 Presenter/Formatter。导出文件另设 Export/Row Mapper，明确列白名单、租户范围和流式处理方式。
- 仅一处使用的简单映射可在调用点完成，不要为每个响应或字段机械新增类。不同 API 版本、外部渠道协议和业务展示格式不得挤进一个万能 Formatter。

## 3. Service / 用例的复用与定时任务调用

- 以业务动作命名 Application 用例（如 `PlaceOrder`、`RebuildInventoryProjection`），每个类聚焦单一用例。模块内可共享的应用级协作逻辑放在该模块 `Application/Services`、`Application/Support` 或明确职责的领域服务中；不要创建不断膨胀的 `CommonService`、`Helper`。
- 多个模块确实共用且稳定、与具体业务无关的基础能力，放入 `app/Modules/Shared`（Shared Kernel），例如 Money、Clock、ID、通用结果类型。Shared 不得依赖业务模块，也不得成为跨模块业务逻辑或随手放置代码的杂物箱。仅一个模块使用的代码留在该模块；跨模块业务协作由发起用例调用目标模块公开接口，不把业务规则搬进 Shared。
- 用例应可脱离 HTTP 入口直接调用。其公开方法使用明确类型的参数（优先不可变 DTO/值对象）和明确返回类型；不得要求 `Request`、`FormRequest`、Session、全局租户状态或 Filament 对象。Artisan Command / Scheduler 负责解析命令选项、建立可信执行者/租户上下文并构造用例输入，再调用同一 Application 用例。
- 参数职责要清晰：入口负责协议解析和基本格式校验；DTO/值对象负责格式化后的约束；用例负责权限上下文、业务前置条件、不变量、幂等与事务。避免在入口和 Service 重复实现一套不一致的业务参数处理，也不能因供 cron 调用而省略服务端验证。
- 定时刷数必须支持明确的租户/范围、`dry-run`（适用时）、批次大小、断点/游标和幂等策略；显式记录操作者或系统来源、request/correlation ID、进度及失败摘要。默认不得无界全量扫描或静默跳过错误。批次之间可恢复，重跑不会产生重复副作用。

## 4. Repository、N+1 与分页/批处理

- Repository 只在复杂查询、持久化隔离、替换边界或测试价值明确时引入；简单 Eloquent CRUD 可直接由模块内 Query/Application 组件承接。不要为每个 Model 强制创建 Repository，也不要把 `query()->get()` 包装成抽象。
- Repository/Query 必须明确租户条件和授权范围；调用者不能通过任意字段、排序表达式或未验证的租户 ID 扩大数据范围。读取只选用所需列，并为关联数据显式 `with()` / `withCount()` 等 eager loading；必要时开启 Laravel 防止 lazy-loading 的开发/测试检查。
- 列表使用有上限的分页，并使用稳定、唯一的排序（通常追加主键作为 tie-breaker）。偏移量很深或数据量大时优先评估基于索引的 cursor pagination；若总数昂贵或并非 UI 必需，避免无意义的 `COUNT(*)`，可采用 simple/cursor paginator。结合真实数据、索引和 `EXPLAIN` 验证，而非只换分页 API。
- 普通分页适合交互式页面；长时间批处理、导出和刷数使用 `lazyById` / `chunkById` 或主键范围分批，避免 offset 在并发新增/删除时漏行或重复。明确排序键、索引、批次大小、内存上限、失败恢复及任务期间数据变化的一致性语义。
- 大型导出优先队列化并流式写入文件/对象存储，不将全量结果载入内存。批处理中的关联数据同样要 eager load 或按批预取；禁止在逐行循环中逐条查询、写入或发送外部请求。批量写入仍须遵守租户隔离、审计、幂等与事务边界。
- “游标”需区分 Laravel `cursor()`（逐条懒加载，单条模型内存占用低，但长时间打开数据库游标/连接且不适合复杂 eager loading）和 `lazyById` / `chunkById`（分批查询、便于关联预取及恢复）。根据驱动、任务时长与关联需求选择；不得将使用 `cursor()` 本身视为性能或一致性保证。需要快照一致性时明确快照/水位线策略，不要为了长任务持有超长事务。

## 5. 基类与通用方法

- 不要求“不同类型文件都必须继承基类”。优先使用组合、接口、Trait（仅适用于无状态且语义稳定的复用）和 Laravel 已有扩展点。只有多个实现确实共享稳定不变量/算法，且基类能减少重复而不隐藏副作用时才抽象基类。
- 禁止为了目录对称而创建空基类、只有转发方法的基类，或把授权、租户过滤、事务、错误吞噬等关键策略藏在难以发现的父类中。基类方法必须有清晰契约、可测试行为和明确覆盖限制；具体类仍应显式声明自身业务行为。
- 通用能力应放在其真实边界：HTTP 请求/响应转换在 Http 层，Application 用例协作在模块 Application 层，领域规则在 Domain 层，查询与持久化在 Infrastructure 层，真正跨模块的基础原语才进入 Shared。不要用 `BaseService` / `BaseRepository` 承载各类无关方法。

## 6. 字典表、Enum 与配置常量的取舍

- 代码编译期固定、有限且随代码发布变更的领域集合，优先使用 PHP backed Enum 或具名常量；例如支付状态、履约状态、内部事件类型。状态转换规则仍由状态机/用例管理，不能仅凭 Enum 赋值绕过转换校验。
- 需要运营人员动态增删/排序/启停、租户自定义、翻译维护、审计、关联引用或数据完整性约束的选项，使用数据库字典/领域表，并通过 Seeder 提供可重复的系统初始数据。租户可配置数据必须包含可信租户范围和唯一约束。
- 环境相关、部署时配置的值放 Laravel `config/*.php`，秘密通过 Secret 注入；不得把部署配置误建成字典表，也不得在业务代码中直接读取 `env()`。
- 不要仅为几项固定状态创建字典表，也不要把需要运营动态管理的选项硬编码成 Enum。若需数据库字典与代码 Enum 同时存在，应定义唯一权威来源、稳定 code、迁移/兼容策略和未知值处理；禁止依赖易变的自增 ID 作为外部协议值。

## 7. 评审检查

新增链路或抽象时，PR 应说明入口如何复用 Application 用例、输入与可信租户/授权上下文如何构造、是否需要独立输出转换、共享代码归属、查询的 N+1/分页策略，以及大批量任务的游标、幂等、恢复和内存控制。增加基类或字典表时说明为何 Enum/组合/现有模块能力不足。相关行为应有 Pest 测试，特别覆盖跨租户拒绝、输出字段白名单、批次边界/重跑和查询数或性能回归（适用时）。
