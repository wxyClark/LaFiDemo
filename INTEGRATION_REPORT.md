# LaFiDemo 集成报告

> 将 `laravel-filament` 与 `laravel12` 两个 Laravel 项目的框架外（业务/自定义）代码，按主项目 **LaFiDemo**（Laravel 13 + Filament 5，模块化 `app/Modules/{Module}/{Application,Domain,Infrastructure,Http}`）的风格，集成到主项目中，并调整存放路径与命名空间。
>
> 生成时间：2026-11-20

---

## 1. 评估结论（5 个仓库）

| 仓库 | 类型 | 是否 Laravel | 处置 |
|------|------|:---:|------|
| `LaFiDemo` | Laravel 13 + Filament 5 多租户电商参考项目 | ✅ | **主项目（集成目标）** |
| `laravel-filament` | Laravel 12 + Filament 3 真实业务项目 | ✅ | 业务代码迁入（跳过 Filament 3 面板） |
| `laravel12` | Laravel 12 骨架 + DEV 工具 | ✅ | 自定义代码完整迁入（Dev 模块） |
| `dnmp` | Docker 环境 | ❌ | 不集成（非 Laravel） |
| `wxyClark.github.io` | Quartz 静态站 | ❌ | 不集成（非 Laravel） |

---

## 2. 集成内容与目标路径（按主项目模块化风格）

### 2.1 laravel-filament → `app/Modules/`

| 源（laravel-filament） | 目标（LaFiDemo） | 命名空间重写 |
|---|---|---|
| `app/Domains/*` | `app/Modules/Domains/*` | `App\Domains\` → `App\Modules\Domains\` |
| `app/CrossSell/*` | `app/Modules/CrossSell/*` | `App\CrossSell\` → `App\Modules\CrossSell\` |
| `app/Infrastructure/*` | `app/Modules/Infrastructure/*` | `App\Infrastructure\` → `App\Modules\Infrastructure\` |
| `app/Services/*` | `app/Modules/Services/*` | `App\Services\` → `App\Modules\Services\` |
| `app/Exports/*` | `app/Modules/Exports/*` | `App\Exports\` → `App\Modules\Exports\` |
| `app/Jobs/*` | `app/Modules/Jobs/*` | `App\Jobs\` → `App\Modules\Jobs\` |
| `app/Models/*` | `app/Modules/Models/*` | `App\Models\` → `App\Modules\Models\` |
| `app/Http/Api`, `Http/Controllers/Api`, `Http/Requests/Api`, `Http/Resources` | `app/Modules/Http/*` | `App\Http\` → `App\Modules\Http\` |
| `app/Console/Commands/*` | `app/Modules/Console/Commands/*` | `App\Console\` → `App\Modules\Console\` |
| `app/helpers.php` | `app/Modules/helpers.php` | `use App\Domains\...` 等已重写 |
| `routes/{api,shop,user,xinfutong,open,public-address,permission}.php` | `routes/laf/*` | `App\Http\...` 已重写 |
| `config/{jwt,permission,xinfutong}.php` | `config/*` | — |
| `database/migrations/*`（40 个，排除 telescope） | `database/migrations/laf/*` | — |
| `resources/views/{address,auth,layouts,permission,shop,xinfutong}` | `resources/views/*` | — |

### 2.2 laravel12 → `app/Modules/Dev/`

| 源（laravel12） | 目标（LaFiDemo） |
|---|---|
| `app/Models/Dev/Query.php` | `app/Modules/Dev/Models/Query.php` |
| `app/Models/User/{User,Role,RoleUserRelation}.php` | `app/Modules/Dev/Models/User/*` |
| `app/Repositories/*` | `app/Modules/Dev/Repositories/*` |
| `app/Services/*` | `app/Modules/Dev/Services/*` |
| `app/Enums/*` | `app/Modules/Dev/Enums/*` |
| `app/Traits/*` | `app/Modules/Dev/Traits/*` |
| `app/Http/Controllers/Dev/*` | `app/Modules/Dev/Http/Controllers/*` |
| `app/Http/Middleware/AdminPermission.php` | `app/Modules/Dev/Http/Middleware/AdminPermission.php` |
| DEV 路由（`/dev`、`/query/*`） | `routes/dev.php`（新建） |
| `resources/views/{dev,profile}` 等 | `resources/views/*` |

命名空间重写：`App\Repositories\` → `App\Modules\Dev\Repositories\`、`App\Services\` → `App\Modules\Dev\Services\`、`App\Enums\` → `App\Modules\Dev\Enums\`、`App\Traits\` → `App\Modules\Dev\Traits\`、`App\Models\Dev\` → `App\Modules\Dev\Models\`、`App\Models\User\` → `App\Modules\Dev\Models\User\`、`App\Http\Controllers\Dev\` → `App\Modules\Dev\Http\Controllers\`、`App\Http\Middleware\` → `App\Modules\Dev\Http\Middleware\`。

> 说明：DEV 模块中 `use App\Http\Controllers\Controller;`（DevController 基类）与 `use App\Models\User;`（Query 模型关联）保留指向主项目自身的 `App\Http\Controllers\Controller` 与 `App\Models\User`，二者在主项目中已存在，可正常解析。

---

## 3. 跳过的内容及原因

| 内容 | 原因 |
|---|---|
| ~~laravel-filament `app/Filament/*`、`app/Providers/Filament/*`~~ | **已迁入**：`app/Filament/Admin` + `AdminPanelProvider`，并做 Filament 3→5 适配，见 `FILAMENT_MIGRATION_REPORT.md` |
| laravel-filament `routes/admin.php`、`routes/auth.php` | 依赖 Filament 面板 / Breeze 认证 |
| laravel-filament `config/telescope.php`、`database/migrations/*telescope*` | 依赖 laravel/telescope 扩展 |
| laravel-filament `resources/views/{filament,vendor}` | Filament 相关视图 |
| laravel12 Breeze 认证代码（`Http/Controllers/Auth`、`Http/Controllers/ProfileController`、`View/Components/*`、`routes/auth.php`） | 框架骨架样板，非业务代码 |
| laravel-filament `lang/*`（850 文件） | 非业务代码（vendor/翻译） |
| `dnmp`、`wxyClark.github.io` | 非 Laravel 项目（按用户确认不集成） |

---

## 4. 已完成的接线

- **`bootstrap/app.php`**：`withRouting(then:)` 加载 `routes/laf/*.php` 与 `routes/dev.php`；`withMiddleware` 注册 `admin.permission` 别名 → `App\Modules\Dev\Http\Middleware\AdminPermission`。
- **`composer.json`**：`autoload.files` 增加 `app/Modules/helpers.php`（`App\Modules\` PSR-4 已存在）。
- **配置**：`config/{jwt,permission,xinfutong}.php` 由 Laravel 自动加载。
- **迁移**：`database/migrations/laf/`（子目录，Laravel 迁移器会递归发现）。
- **视图**：合并至 `resources/views/*`（未覆盖主项目已有文件；`filament`/`vendor` 除外）。

---

## 5. 已知注意事项与后续步骤

> ⚠️ 集成后项目**未保证立即可运行**（主项目为 Laravel 13 + Filament 5，源项目为 Laravel 12 + Filament 3，存在跨版本差异）。以下为后续必要工作：

1. **补依赖**（源项目使用、主项目可能缺失）：
   - `tymon/jwt-auth`、`overtrue/laravel-pinyin`、`spatie/laravel-permission`、`kra8/laravel-snowflake`、`mk-j/php_xlsxwriter`、`barryvdh/laravel-dompdf`、`laravel/horizon`（部分主项目已有）。
   - 需在 `composer.json` `require` 中添加后执行 `composer update`。
2. **重新生成优化 classmap**：`composer dump-autoload -o`。源项目 CrossSell 存在「namespace 与物理目录不一致」（如 `app/CrossSell/AfterSale/Enums/AfterSaleStatus.php` 声明 `App\CrossSell\Enums`），依赖 Composer 优化 classmap 解析；集成后同样需 `-o`。
3. **Filament 3 → 5 适配**：被跳过的 `app/Filament/*` 如需迁入，需按 Filament 5 API 重写（Resource/Pages/表单/表格 等）。
4. **迁移冲突**：`database/migrations/laf/*` 与主项目基线迁移存在同名表（`users`、`cache`、`jobs`、`products`、`sessions`），真实接入时需合并/改名迁移，避免 `migrate` 冲突。
5. **认证/守卫**：`routes/laf/api.php` 等使用 `auth:admin-api`、`auth:api` 守卫与 JWT，需在 `config/auth.php` 配置对应 guard；DEV 路由依赖 `auth`（web）守卫。
6. **视图**：`routes/laf/{shop,xinfutong}.php` 引用 `view('shop.*')`、`view('xinfutong.*')` 等视图（已复制，若未覆盖请确认）。
7. **源项目原有坏引用**（集成后仍存在，属源项目自身问题）：`App\Modules\Domains\CrossSell\Models\{Order,Product,SKU,Tenant,Warehouse}`、`App\Modules\Domains\CrossSell\Enums\{OrderStatus,PlatformType,ProductStatus}` 及 `App\Modules\CrossSell\Database\Factories\*` 在源项目中即未实现/无对应类，需按业务补全。

---

## 6. 统计

- `app/Modules` PHP 文件：**272**（含主项目原有 ~25）
- 集成路由：`routes/laf/*` 7 个 + `routes/dev.php` 1 个
- 集成迁移：`database/migrations/laf/*` **40** 个
- 集成配置：`jwt`、`permission`、`xinfutong` 3 个
- 已校验：所有集成 PHP 文件 `namespace`/`use` 已重写为 `App\Modules\...`；无残留 `App\Domains\`、`App\CrossSell\`、`App\Services\` 等旧前缀引用。
