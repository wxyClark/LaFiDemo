# 基础架构评审：现状盘点、风险清单与一致性修复建议

> 评审基线：分支 `docs/foundation-audit`（基于 main `cecb27e`）。
> 评审范围：依赖与运行时基线、应用骨架、质量门禁、测试环境差异、治理文档一致性。
> 评审性质：只读文档评审，不修改任何代码、流水线或依赖文件。
> 结构：§1 现状盘点、§2 风险与缺口清单；§3 一致性修复建议随后续提交追加。

## 1. 现状盘点（设计声称 vs 实际落地）

### 1.1 依赖与运行时基线

| 层 | 设计声称 | 实际落地 | 结论 |
| --- | --- | --- | --- |
| PHP 基线 | AGENTS.md:7 声明 PHP `^8.4.3`，生产用 8.4 最新安全补丁；components:9 结论一致 | composer.json:15 `"php": "^8.4.3"`，composer.json:72-74 platform 固定 8.4.3；composer.lock:12786-12791 platform 一致 | 一致。composer.lock:12811-12812 `platform-overrides: 8.4.3` 仅约束依赖解析，不代表运行时补丁策略，与 components:22"不得把 8.4.3 当作生产补丁"不矛盾，需部署侧镜像策略约束 |
| 技术栈 | AGENTS.md:8 声明 Laravel 13、Filament 5、MySQL 8.4 LTS、Redis、Pest 4 | composer.json:42-53 `laravel/framework ^13.0`、`filament/filament ^5.0`、`pestphp/pest ^4.0`；CI 服务 mysql:8.4 / redis:8（ci.yml:15,27） | 一致。MySQL 对齐 8.4 LTS；Redis 具体大版本未在任何文档锁定（technology-selection.md:34 仅要求"锁定稳定受维护版本"），CI 使用 redis:8 属首个事实锁定但文档未回写 |
| 关键组件 | AGENTS.md:10 指向 components 文档 | composer.json:37-46 与 components:33-47 组件表逐项对应（brick/money、yansongda/pay、openspout、dompdf、horizon、sanctum、qrcode、intervention/image） | 一致。composer.lock 已生成并提交（17f5072），components:3 状态描述过时 |
| PHP 扩展 | components:60 要求生产至少检查 bcmath/gd/intl/mbstring/openssl/pdo_mysql/redis/xml/zip | composer.json:16-36 声明 ext-bcmath 至 ext-zip（含 ext-redis、ext-pdo_mysql、ext-gd、ext-intl）；ci.yml:43 CI 安装全部扩展 + pdo_sqlite/sqlite3（测试用） | 一致 |

### 1.2 应用骨架（app/、bootstrap/、config/）

| 层 | 设计声称 | 实际落地 | 结论 |
| --- | --- | --- | --- |
| Laravel 骨架 | architecture.md:31-55 与 blueprint §2.1 规划 `app/Modules/*`、双 Filament Panel；blueprint:193 阶段 1 要求 TenantContext + 两个 Panel | `app/` 仅 `Http/Controllers/Controller.php`、`Models/User.php`、`Providers/AppServiceProvider.php`（Laravel 13 官方骨架）；`bootstrap/app.php` 未注册 api 路由（仅 web/commands/health）；无 `routes/api.php` | 骨架已落地，**尚无任何业务模块**；与架构文档"不代表已实现"的定位一致，属预期中间态 |
| 数据库 | blueprint §5 规划 tenants/products/orders/payments/outbox 等数十张表 | `database/migrations/` 仅 3 个官方骨架迁移（users/cache/jobs）+ UserFactory + 空 DatabaseSeeder | 无业务表；TenantContext、membership、租户隔离测试均未落地（blueprint:334-336 阶段门槛未完成） |
| Filament Panel | AGENTS.md:9、architecture.md 双 Panel（Platform/Tenant）；components:70 初始化验收第 3 项要求双 Panel 可启动 | Filament 已声明并锁定，但 `app/Filament/` 不存在、无 Panel Provider 注册 | 未落地，components §4 验收项未完成 |
| 可观测性 | AGENTS.md:23 结构化日志/审计/correlation ID | 无日志 channel 定制、无中间件；`phpunit.xml:33-35` 引用 PULSE/TELESCOPE/NIGHTWATCH env 占位但项目未使用这些包 | 仅框架默认 |

### 1.3 质量门禁（CI 检查项 vs docs/rules 承诺）

| 规则承诺 | 规则出处 | CI 现状 | 缺口 |
| --- | --- | --- | --- |
| composer validate / audit / pint / phpstan / pest / migrate:fresh --seed | testing-security.md:41-48 | ci.yml:49-105 六项齐全：validate、audit、pint --test、phpstan analyse、pest、Redis 冒烟、MySQL 8.4 迁移+seed | `composer validate` 未加 `--strict`（规则:42 要求 `--strict`） |
| CI 固定 PHP/MySQL/Redis 主版本 | delivery-and-operations.md:21；data-and-cache.md:77 | ci.yml:14-34 mysql:8.4 + redis:8 服务、PHP 8.4（ci.yml:42） | 无缺口 |
| Pest 覆盖业务矩阵（跨租户、幂等、库存并发等） | testing-security.md:14-34 | tests/ 仅 2 个示例测试（tests/Unit/ExampleTest.php、tests/Feature/ExampleTest.php） | 业务测试体量为 0（骨架阶段预期），门禁无最低用例数/覆盖率约束 |
| 有条件执行 SAST / Secret 扫描 / 依赖扫描 | workflow.md:117 第 6 条；testing-security.md:80-81 | CI 仅 `composer audit`（Composer 漏洞库） | 无 secret 扫描、无 SAST、无运行时镜像/SBOM 扫描（components:25 有要求）；规则措辞为"有条件时"，非当前阻断 |
| 覆盖率 | rules 未承诺 coverage | ci.yml:44 `coverage: none` | 无缺口（规则未承诺），列为可后续项 |
| PHPStan 基线管理 | testing-security.md:55"禁止无限添加忽略项" | phpstan.neon.dist level 5，无 baseline 文件 | 无缺口 |
| 门禁命令单一事实来源 | testing-security.md:39"命令写入 composer.json/CI" | 命令内联在 ci.yml，composer.json 无 `scripts` 段 | 不一致：无 `composer test` 等脚本，本地与 CI 执行路径不统一 |

### 1.4 测试环境差异（SQLite vs MySQL 8.4）

| 项 | 现状 | 结论 |
| --- | --- | --- |
| 本地/单测引擎 | phpunit.xml:27-28 `DB_CONNECTION=sqlite`、`:memory:`；.env.example:22 默认 sqlite | 本地 Pest 跑在 SQLite |
| CI 引擎 | ci.yml:61-62 Pest 步骤未覆盖 DB_* env，继承 phpunit.xml 的 SQLite；migrate:fresh --seed 单独设 `DB_CONNECTION=mysql`（ci.yml:95-105） | 门禁内"测试"与"迁移"跑在不同引擎 |
| 规则要求 | testing-security.md:11"MySQL 特有约束的测试必须在 MySQL CI 环境运行"；data-and-cache.md:77；blueprint:187"不得只用 SQLite 假设覆盖 MySQL 并发/约束语义" | 首个库存/幂等并发测试落地时，当前 CI 配置将偏离规则承诺的测试环境，须一并修正 CI 的 Pest 步骤 env |
| 迁移双引擎兼容 | 3 个骨架迁移（users/cache/jobs）均为通用列类型，SQLite 与 MySQL 8.4 均可执行 | 当前无兼容故障；blueprint §3 约定的组合唯一键、DECIMAL、CHECK 约束、库存条件更新等未来 MySQL 特性一旦使用将导致 SQLite 失败或静默降级，属持续风险 |

### 1.5 治理文档一致性（AGENTS.md 引用路径与规则冲突）

| 项 | 现状 | 结论 |
| --- | --- | --- |
| AGENTS.md 引用路径 | AGENTS.md:11 引用 docs/architecture/architecture.md、docs/architecture-blueprint.md、docs/technology-selection.md、docs/rules/ | 全部存在；docs/README.md 导航完整；docs/adr/、docs/reviews/ 此前不存在（本评审新建） |
| PHP 基线冲突 | technology-selection.md:15,31 声明"PHP 8.3 为兼容下限""最低基线为 8.3" | 与 AGENTS.md:7（^8.4.3）、components:9（8.4.3 最低）、composer.json:15（^8.4.3）直接矛盾；technology-selection 为早期决策，被 components"2026-11-03 依赖解析修订"推翻但未回写 |
| 文档状态过时 | components:3 声称"仓库仍没有完整骨架、未生成 composer.lock" | 17f5072 已生成骨架并锁定依赖，状态描述过时 |
| 门禁措辞差异 | testing-security.md:42 `composer validate --strict` vs ci.yml:50 `composer validate` | 规则比 CI 严格，CI 未对齐 |
| 规则重复 | AGENTS.md 与 rules 多文件存在等价重述（租户隔离、金额整数等） | 属有意分层摘要，rules/README.md:17 明确 AGENTS.md 为"全局摘要"，无冲突 |

## 2. 风险与缺口清单

分级：**阻断**（阻止阶段 1 业务开发或产生错误前提）、**重要**（须在首个业务模块落地前修复）、**可后续**（不阻塞当前阶段）。

### 2.1 阻断（1 条）

| 编号 | 风险 | 证据 |
| --- | --- | --- |
| R-01 | **PHP 基线文档矛盾**：technology-selection.md 声称最低 PHP 8.3，与 AGENTS.md / components / composer.json（^8.4.3）冲突。后续开发者/代理按该文档可能引入 8.3 兼容假设，违背 CVE-2024-4577 基线与 CI（PHP 8.4）。 | technology-selection.md:15,31,53 vs AGENTS.md:7、architecture-components.md:9、composer.json:15 |

### 2.2 重要（5 条）

| 编号 | 风险 | 证据 |
| --- | --- | --- |
| R-02 | **CI 中 Pest 跑 SQLite、迁移跑 MySQL**：与 testing-security.md:11、data-and-cache.md:77"MySQL 特有行为必须在 MySQL CI 环境运行"冲突；首个库存/幂等并发测试将被 SQLite 语义误判或无法编写。 | phpunit.xml:27-28 vs ci.yml:61-62（Pest 步骤未覆盖 DB_* env）、ci.yml:95-105 |
| R-03 | **composer.json 无 scripts 段**：与 testing-security.md:39"命令写入 composer.json/CI 并保持单一事实来源"不一致，本地与 CI 执行路径可能漂移（含 `--strict` 缺失）。 | testing-security.md:39,42 vs composer.json（无 scripts）、ci.yml:50 |
| R-04 | **模块布局无强制规则**：architecture.md:31"建议目录"、blueprint §2.1"推荐"；rules 未规定模块代码必须落在 `app/Modules/<Domain>`，未规定跨模块调用边界与迁移归属。首个业务模块落地时缺乏可执行判定依据（由 ADR-0001 固化）。 | coding-standards.md:35-41（仅描述分层）、architecture.md:31,57 |
| R-05 | **Redis/MySQL 镜像版本未回写文档**：technology-selection.md:34 要求锁定后记录，CI 实际使用 redis:8 / mysql:8.4 镜像（ci.yml:15,27）未在任何文档登记，违反 AGENTS.md:10 组件决策单一出处。 | ci.yml:15,27 vs technology-selection.md:19,34 |
| R-06 | **质量门禁缺 secret 扫描**：AGENTS.md:20 要求"不提交密钥"，但无任何自动化手段（CI 无 gitleaks 等步骤）；yansongda/pay 引入后支付证书/私钥经仓库流转风险上升。 | ci.yml（无 secret 扫描 step）、docs/prompts/workflow.md:117（仅"有条件时"） |

### 2.3 可后续（4 条）

| 编号 | 风险 | 证据 |
| --- | --- | --- |
| R-07 | components:3 状态行过时（"未生成 composer.lock"），文档读者误判项目阶段。 | architecture-components.md:3 |
| R-08 | 无 SAST / 运行时 SBOM 扫描；components:25 要求"扫描运行时镜像/SBOM"，当前仅覆盖 Composer 漏洞库。 | architecture-components.md:25 vs ci.yml:52-53 |
| R-09 | 无覆盖率门禁；业务矩阵（testing-security.md:14-27）无量化下限，示例测试全绿即可通过。 | ci.yml:44,61-62、tests/ 目录 |
| R-10 | phpunit.xml:33-35 PULSE/TELESCOPE/NIGHTWATCH env 占位与项目未使用包不一致；未来接入需确认配置来源。 | phpunit.xml:33-35 |
