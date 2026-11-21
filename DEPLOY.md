# 部署与运行

本文档分两部分：**本地最小运行**（已在本机验证可跑通，不依赖 Docker、不依赖任何额外软件）与**生产部署要点**。

---

## 一、本地最小运行（无 Docker / 无额外软件）

### 1. 前置条件（仅这些）

| 组件 | 要求 | 本机实际 |
|---|---|---|
| PHP | `^8.4.3`（`composer.json` 约束） | **8.5.13** NTS VS2022 x64，位于 `C:\php` |
| 扩展 | `pdo_sqlite`、`sqlite3`、`mbstring`、`openssl`、`curl`、`intl`、`fileinfo`、`gd`、`zip`、`bcmath`、`dom`、`simplexml` | 已启用 |
| 数据库 | sqlite（`DB_CONNECTION=sqlite`，库文件 `database/database.sqlite`） | 已就绪 |
| Composer | 2.x | 2.11.2（`C:\php\composer.bat`） |

> PHP 版本说明：`8.5.12` 起修复了 php2shell 漏洞（CVE-2026-109618 / 109619），故本地选用 8.5.13；`composer.lock` 要求 `>= 8.5.12`，请勿降到 8.5.11 或更早。

无需 Redis / MySQL / npm / node：

- `.env` 中 cache / session / queue 均为 `database` 驱动，缓存、会话、队列全部落 sqlite。
- Filament 的前端资源由包内**预编译**产物提供（`vendor/filament/filament/dist`），通过 `artisan filament:assets` 复制到 `public/`，**不需要 `npm run build`**。

### 2. 初始化与启动（三步）

```powershell
cd C:\Work\LaFiDemo

# ① 数据库（首次或重置）
C:\php\php.exe artisan migrate:fresh --force

# ② 前端资源：把 Filament 预编译的 js/css/fonts 复制到 public/
C:\php\php.exe artisan filament:assets

# ③ 启动
C:\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

`C:\php` 已加入用户 PATH 时，把 `C:\php\php.exe` 换成 `php` 即可。

> ⚠️ **② 不可省略**：不执行 `filament:assets` 时，面板**能登录但样式全丢**（`/js/filament/*`、`/css/filament/*`、`/fonts/filament/*` 全部 404）。每次 `composer update` 更新 filament 包后也要重跑一次。

### 3. 数据库与账号

```powershell
C:\php\php.exe artisan db:seed --class=DemoAccountsSeeder --force
```

`DemoAccountsSeeder` 幂等（`firstOrCreate`），可重复执行。

### 4. 访问入口

应用主站：`http://127.0.0.1:8000/`

| 面板 | 入口 | 登录页 | 账号 | 密码 | 认证来源 |
|---|---|---|---|---|---|
| 管理端 | `/admin` | `/admin/login` | `admin@local.dev` | `admin123456` | `admins` 表，`admin` 守卫 |
| 平台端 | `/platform` | `/platform/login` | `platform@local.dev` | `platform123` | `users` 表，`role=platform` |
| 租户端 | `/tenant` | `/tenant/login` | `tenant@local.dev` | `tenant123456` | `users` 表，`role=tenant` + 激活租户 |

- 租户端还要求 `ResolveTenantContext` 通过：该用户在 `tenants` 表中存在且 `status=active`（种子已保证）。
- 权限边界由 `FilamentUser::canAccessPanel()` 裁决，错配账号会被拒绝（`/admin` 只认 `admins` 表，平台/租户用户无法越权）。

### 5. 已验证

- 登录页 `/admin/login`、`/platform/login`、`/tenant/login` 均返回 **200**；未登录访问面板 **302** 跳各自登录页。
- 登录页引用的 11 个静态资源（`/js/filament/*`、`/css/filament/filament/app.css`、`/fonts/filament/...`）在 `filament:assets` 后**全部 200**。
- 三组凭据经 `Auth::guard('admin')->attempt()` / `Auth::attempt()` 校验全部通过；登录后 `/admin`、`/platform`、`/tenant` 三个面板仪表盘均渲染 **200**。
- **全量测试通过**：`php artisan test`（Pest）**35 个测试 / 137 断言，0 失败**。其中 `AdminPanelSmokeTest` 覆盖三个面板 + 全部 26 个 Admin 资源索引页 + 19 个创建页。
- `route:list`：admin 86 条、platform 5 条、tenant 9 条；`migrate:fresh` 全部 DONE；`composer audit` 无告警；全项目 `php -l` 0 失败。

### 5.1 Filament 3 → 5 兼容修复（本轮已处理）

集成后逐页渲染暴露并修复了以下迁移问题（均无需改业务逻辑，只对齐 Filament 5 API）：

| 问题 | 修复 |
|---|---|
| `Filament\Tables\Actions\*`（View/Edit/Delete/BulkActionGroup…）不存在 | 改用 `Filament\Actions\*`（26 个资源，命名空间前缀引用） |
| `Filament\Resources\Components\Tab` 不存在 | 改用 `Filament\Schemas\Components\Tabs\Tab`（3 个 List 页） |
| `Filament\Forms\Components\Section` / `Infolists\Components\Section` 不存在 | 改用 `Filament\Schemas\Components\Section`（24 个资源） |
| `TextInput::decimals()`、`CheckboxList::bulkToggleAll()` 已移除 | 删除对应调用（各 1 处） |
| `products` 表缺 `deleted_at`/`name`/`type`/`image`（模型用 `SoftDeletes`） | `0003_01_01_000000_create_products_table.php` 补可空列 |
| `admin_audit_logs` 表从未创建（模型存在但无迁移） | 新增 `2026_07_23_000001_create_admin_audit_logs_table.php` |
| 关系/模型引用类不存在（`CrossSell\...\Order`/`User`/`SKU`/`AdminAuditLog`） | 补/改 `use` 指向实际存在的类 |
| `RoleResource` / `AuditLogResource` 裸 `can()` 无 super_admin 旁路 | 加 `hasRole('super_admin')` 旁路；seeder 同时授予 `role.*` 权限 |

> 这些修复是让面板页可真正渲染所必需；`AdminPanelSmokeTest` 会在后续改动后自动回归。

### 6. 本地已知限制

- `laravel/horizon` 依赖 `ext-pcntl` / `ext-posix`（仅 Unix）。Windows 上它以 `--ignore-platform-req` 安装，**不能作为 worker**；本地队列用 `php artisan queue:work` 或 `sync` 驱动。
- 登录页预填（`AutoFillLogin` 读取 `.env` 的 `FILAMENT_ADMIN_PREFILL_EMAIL` / `FILAMENT_ADMIN_PREFILL_PASSWORD`）是**本地便利功能**，会把凭据写进登录页 HTML；生产环境请移除这两行。
- Filament 3 → 5 的遗留人工项（`ImageUpload` 适配、Livewire v4、Tailwind v4 主题）见 [FILAMENT_MIGRATION_REPORT.md](FILAMENT_MIGRATION_REPORT.md)。
- `public/` 下的 `js/`、`css/`、`fonts/` 目录是生成物，可加入版本控制忽略清单。

---

## 二、生产部署要点

沿用 [README.md](README.md) 的技术路线：PHP 8.4+（推荐 8.4.3+ 或 8.5.12+）、MySQL 8.4 LTS、Redis（缓存 / 会话 / 队列 / 限流）、Laravel Horizon。

```powershell
composer install --no-dev --optimize-autoloader
# 配置 .env：DB_CONNECTION=mysql、REDIS_*、APP_ENV=production、APP_KEY 已生成
php artisan key:generate            # 若尚无 APP_KEY
php artisan migrate --force
php artisan db:seed --class=DemoAccountsSeeder --force   # 生产按实际账号替换或改用其它 seeder
php artisan filament:assets         # 无定制主题时直接用预编译产物
# 若定制了 Filament 主题/资源，改为前端构建：npm ci && npm run build
php artisan config:cache route:cache view:cache
# 队列（Linux 环境）
php artisan queue:work --tries=3 --timeout=90   # 或直接使用 Horizon
```

要点：

- 生产用 MySQL 后，`database/migrations-skipped/` 中隔离的 5 个同名表冲突迁移（`sessions` / `cache` / `jobs` / `products` / `add_teams_fields`）不应启用，除非明确需要。
- 登录守卫：`admin`（`admins` 表）与 `web`（`users` 表，平台/租户按 `role` 区分），见 `config/auth.php`。
- 清理本地便利项：删除 `.env` 中的 `FILAMENT_ADMIN_PREFILL_*`。
