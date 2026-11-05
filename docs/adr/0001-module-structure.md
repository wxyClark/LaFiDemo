# ADR-0001 模块目录结构与跨模块调用规则

> 状态：**Approved**
> 起草：2026-11-05，基础架构评审任务
> 决策人 / 批准日期：wxyClark（项目负责人）/ 2026-11-05（按项目持续指令批准；批准范围限于本 ADR 拟议决策 a-h 条）

## 背景

项目采用模块化单体（AGENTS.md:9；architecture.md:29）。`docs/architecture/architecture.md:31-55` 与 `docs/architecture-blueprint.md` §2.1 给出了 `app/Modules/<Domain>` 的**建议**目录树，但两处均标注"建议/推荐"，`docs/rules/coding-standards.md`"模块化单体"一节（:35-41）只规定了模块划分（Tenancy、Catalog、Inventory、Cart、Checkout、Order、Payment、Promotion、Store、Distribution）与"公开应用接口是唯一写入入口"的原则，**未规定**：

1. 模块代码必须落在哪个目录（`app/Modules/<Domain>` 还是 `app/Domain/<Domain>` 等布局）；
2. 模块内部分层（Domain/Application/Infrastructure/Http）的最低落地形态；
3. 模块的迁移文件归属与命名；
4. 跨模块写入/读取的可执行判定规则（rules 只给原则，无目录级约束）；
5. composer autoload 映射方式。

当前 `app/` 尚无业务模块（仅 Laravel 13 官方骨架：`Http/`、`Models/User.php`、`Providers/`），本 ADR 需在**第一个业务模块落码前**固化布局，避免事后返工。曾评估过一套未提交的 `app/Modules/` 脚手架，本 ADR 仅以"曾考虑的布局"为背景，不以其实现细节为准。

## 决策驱动因素

- 与 `docs/rules/coding-standards.md:35-41`"模块化单体"规则及 `application-flow-and-data-access.md`（Shared 位于 `app/Modules/Shared`，:39）保持一致——rules 已隐含 `app/Modules/` 前缀。
- 多租户、金额、库存不变量等规则要求模块边界可被静态检查与 code review 显式判定（目录级隔离比命名空间级约定更易审查）。
- 单仓库模块化单体：一个 `composer install`、一套 CI，不引入模块独立打包/monorepo 工具（AGENTS.md:9 禁止无评审新增通用抽象框架）。
- 未来团队并行开发不同模块，目录即边界，减少合并冲突与误改。

## 备选方案

### 方案 A：`app/Modules/<Domain>`（按模块聚合，模块内再分层）

```
app/Modules/
  Tenancy/
    Domain/         # 实体、值对象、不变量
    Application/    # 用例、DTO、Policy 协作入口
    Infrastructure/ # Eloquent 模型、持久化、队列
    Http/           # 该模块的 FormRequest、API Resource（薄）
    Migrations/     # （可选，见拟议决策 §d）
```

- 收益：目录即边界，review 时一眼判定跨模块改动；模块可整体迁移/拆分；与 architecture.md:31-55、blueprint §2.1 现有文档树**零修改对齐**；PSR-4 一条映射 `"App\\Modules\\": "app/Modules/"` 即可。
- 代价：与 Laravel 官方骨架的 `app/Models`、`app/Http` 全局分层习惯不同，新人需学习模块内约定；共享 `users` 表（Laravel 骨架自带）留在骨架，业务模型归模块，双轨并存需说明。

### 方案 B：`app/Domain/<Domain>`（按层聚合，类似 Laravel 默认 + Domain 扩展）

```
app/Domain/Order/...（或用例平铺 app/Orders/...）
app/Http/Controllers/...（全局 Http 层）
app/Models/...（全局 Model 层）
```

- 收益：贴近 Laravel 默认分层，入门成本低；`app/Http`、`app/Models` 无需拆分。
- 代价：**与现有文档冲突**——architecture.md:31-55、blueprint:113-150、rules 中 Shared 路径 `app/Modules/Shared`（application-flow-and-data-access.md:39）均已写死 `app/Modules/` 前缀，选 B 需先改 4+ 份文档并说明理由；跨模块边界依赖命名空间约定而非目录，code review 判定成本高；全局 `app/Models` 会被各模块模型污染，失去"目录即边界"。

**结论**：选 A。与既有文档树一致、零文档改写成本（除补强制规则）、边界可静态判定，符合最小改动原则。

## 拟议决策

在 A 的基础上，固化以下规则（与 coding-standards.md"模块化单体"一节并列，冲突处以 rules 为准）：

a. **模块代码必须落在 `app/Modules/<Domain>/`**，`<Domain>` 限定为 coding-standards.md:37 列出的十模块（Tenancy、Catalog、Inventory、Cart、Checkout、Order、Payment、Promotion、Store、Distribution）加 `Shared`。新增模块需先修订该清单并经评审。

b. **模块内分层按复杂度采用 `Domain/ / Application/ / Infrastructure/ / Http/`**（architecture.md:57、blueprint:152 已有约定），最低形态：用例与 Policy 在 `Application/`，Eloquent 模型在 `Infrastructure/`，入口在 `Http/`；简单模块可省略 Domain 层，不得机械四层。

c. **Policy 归属**：模块自有资源的 Laravel Policy 放在该模块 `Application/`（或模块内 Policies/），不统一放 `app/Policies`；跨租户/平台的 Policy 放 `Tenancy` 模块。

d. **迁移归属**：业务表迁移按模块拆分命名并保留在 `database/migrations/` 单目录（`phpunit.xml` 与 CI 已按该路径验证，ci.yml:95-105），命名前缀带模块序，如 `0002_..._create_tenants_table.php`（沿用 blueprint §6 的顺序建议）；**不**在模块目录内另设 Migrations 目录，避免双迁移路径。模块名只体现在迁移文件命名与注释。

e. **跨模块调用**：
   - 写入：只能调用目标模块 `Application/` 暴露的用例（公共接口），禁止跨模块直接 `Model::query()` 写对方表（与 rules:38 一致，落实为目录规则：不得 `use App\Modules\X\Infrastructure` 中其他模块的类，除非是 `Shared` 或对方公开接口）。
   - 读取：通过对方公开的查询接口/投影；确需读对方模型时以只读投影形式封装在对方 `Application/`。
   - 静态兜底：后续可加 PHPStan 规则/ArchUnit 式测试（tests/Architecture/，blueprint:148）强制本条，列为可后续项。

f. **Shared**：`app/Modules/Shared/` 仅放 Money/Clock/ID/通用结果类型（application-flow-and-data-access.md:39），不得依赖任何业务模块，不得成为杂物箱。

g. **autoload**：composer.json 增加 `"App\\Modules\\": "app/Modules/"` PSR-4 映射（现有 `App\\: app/` 保留，骨架代码不动）。

h. **Filament**：双 Panel 入口保持 `app/Filament/Platform/`、`app/Filament/Tenant/`（architecture.md:47-50），Panel 内只调模块 Application 用例（rules:55-56），不复用对方 Http 层。

## 后果 / 风险

- 正面：目录边界可审查、可静态检查；文档树零改写；模块未来独立拆分成本低。
- 风险：与 Laravel 默认习惯不同，需 1 次 onboarding 说明；`users` 表（骨架）与 Tenancy 模块 membership 的归属需 Tenancy 模块首任务中明确（users 暂留骨架，membership/roles 归 Tenancy）。
- 不改变 CI/迁移验证路径（迁移仍在 `database/migrations/`）。

## 验证方式

1. 首个业务模块（预期 Tenancy）落地时，目录结构符合 a-h；
2. `composer dump-autoload` 后 PHPStan（phpstan.neon.dist，paths 含 app）可解析模块命名空间；
3. 增加一条 Architecture 测试（tests/Architecture/）断言：非本模块代码不得 `use` 其他模块 `Infrastructure/` 下的类（e 的兜底）；
4. 跨租户拒绝测试（AGENTS.md:15）在模块用例层通过。

## 复核触发条件

- 需要把某模块拆为独立服务/独立仓库时；
- 团队规模或模块数量增长到单仓库 review 明显困难时；
- Laravel/Filament 主版本升级带来骨架目录结构变化（如 `app/` 官方分层调整）时；
- 静态检查工具无法表达 e 的依赖规则、需要更强隔离（独立 autoload 映射/子仓库）时。

## 决策人 / 日期

wxyClark（项目负责人）/ 2026-11-05 批准（按项目持续指令；复核触发条件见上节）。

## 与既有规则的关系

- 与 `docs/rules/coding-standards.md:35-41`、`application-flow-and-data-access.md:24,39`、AGENTS.md:16"业务边界"无冲突；本 ADR 是其目录级落实。
- 若实现中发现 rules 与本 ADR 冲突（如未来 Shared 扩容），以 rules 为准并更新本 ADR（AGENTS.md:3 冲突顺序：安全/法律 > AGENTS.md > 技术专项规则）。
