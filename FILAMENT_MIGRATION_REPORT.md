# Filament 3 → 5 适配报告（集成到 LaFiDemo）

> 将 laravel-filament（Filament 3.3）的 Filament 面板代码迁入主项目 LaFiDemo（Filament 5），并做 3→5 API 适配。
> 生成时间：2026-11-20

---

## 1. 迁移内容

| 源（laravel-filament） | 目标（LaFiDemo） | 说明 |
|---|---|---|
| `app/Filament/Admin/*`（113 个 PHP 文件） | `app/Filament/Admin/*` | 资源 29 + 页面 + 导出器 + 组件 |
| `app/Providers/Filament/AdminPanelProvider.php` | `app/Providers/Filament/AdminPanelProvider.php` | 管理后台面板 |

命名空间已重写：`App\Domains\` → `App\Modules\Domains\`、`App\CrossSell\` → `App\Modules\CrossSell\`、`App\Infrastructure\` → `App\Modules\Infrastructure\`、`App\Services\` → `App\Modules\Services\`、`App\Exports\` → `App\Modules\Exports\`、`App\Jobs\` → `App\Modules\Jobs\`、`App\Models\` → `App\Modules\Models\`、`App\Http\` → `App\Modules\Http\`；`App\Filament\*` 保持不变。

## 2. 已自动应用的 Filament 3→5 机械变更

| 变更 | 数量 | 说明 |
|---|---|---|
| `Tables\Actions\{Action,BulkAction,BulkActionGroup,DeleteAction,DeleteBulkAction,EditAction,ViewAction}` → `\Filament\Actions\...` | 100 处 | v4 统一 Actions 命名空间 |
| `Tables\Actions\ExportBulkAction` → `\Filament\Actions\ExportAction` | 2 处 | 导出动作（需复核类名） |
| `function getHeaderActions()` → `function headerActions()` | 58 处 | v4 页面方法更名 |
| `->reactive()` → `->live()` | 1 处 | v4 更名 |
| 残留 `Tables\Actions\` / 旧命名空间前缀 | 0 处 | 已校验 |

## 3. 已完成的接线

- **`bootstrap/providers.php`**：注册 `AdminPanelProvider::class`。
- **`config/auth.php`**：新增 `admin` 守卫（session + `admins` provider → `App\Modules\Models\Admin`）。
- **Admin 模型**（`app/Modules/Models/Admin.php`）：实现 `FilamentUser`、`JWTSubject`，`$guard = 'admin'`。

## 4. 需人工复核/后续处理（无法在无 composer 运行时下自动验证）

> ⚠️ 以下为 Filament 3→4/5 的已知破坏性变更，**必须处理**才能让管理后台运行。

### 4.1 依赖（必装）
- `composer require`：`tymon/jwt-auth`、`overtrue/laravel-pinyin`、`spatie/laravel-permission`（Admin 模型使用 `HasRoles`/`JWTSubject`）。
- 之后执行 `composer dump-autoload -o`（CrossSell 命名空间与物理目录不一致，依赖 classmap）。

### 4.2 代码级必改项
1. **FileUpload 图片编辑**（`ProductResource.php`、`CrossSellProductResource.php`）：`->imageResizeMode('cover')->imageCropAspectRatio('1:1')->imagePreviewHeight('200')` 在 v4 已移除，改为 `->imageEditor()` 配置：
   ```php
   FileUpload::make('image')->image()->directory('products')->maxSize(1024)
       ->imageEditor()          // v4+：resizeMode/aspectRatio 移入 ImageEditor
       ->columnSpanFull(),
   ```
   （imagePreviewHeight 无直接对应，预览尺寸需在 imageEditor 中配置，需人工确认。）
2. **`FilamentInfoWidget`**（`AdminPanelProvider`）：v4 已移除，从 `->widgets([...])` 中删除 `Widgets\FilamentInfoWidget::class`。
3. **`->sidebarWidth('16rem')`**（`AdminPanelProvider`）：v4 面板侧栏宽度 API 有变，需确认是否保留（或改用配置）。
4. **`Color::Amber`**：v4 调色板 API 有变，确认 `Color::Amber` 仍可用，否则改用 `Color::hex(...)`。
5. **Section/Grid/Fieldset 默认列宽**：v4 起默认占 1 列；`ProductResource` 中 `Section::make(...)->columns(2)` 需视布局补 `->columnSpanFull()`。
6. **`deferFilters()`**：v4 默认延迟筛选；如需即时筛选补 `->deferFilters(false)`。
7. **`unique(ignoreRecord: true)`**：v4 默认即 `ignoreRecord: true`，现有调用冗余但无害。
8. **`columnSpan()` 默认 >= lg**：确认用到 `columnSpan(数字)` 的地方是否符合预期。
9. **枚举字段状态**：v4 起 `Select` 的 enum 状态始终返回枚举实例；核对 `->formatStateUsing(fn (string $state) ...)`、`afterStateUpdated` 的类型。
10. **URL 参数更名**：`activeRelationManager`→`relation`、`activeTab`→`tab` 等；若代码用 `::getUrl()` 传旧参数需更新。
11. **`FilamentUser` 契约命名空间**：确认 `Filament\Models\Contracts\FilamentUser` 在 v5 的命名空间（可能已变更）。
12. **`Notification::make()->warning()/->info()/->success()/->danger()`**：确认 v5 是否仍支持（部分已废弃，改 `->color()`/`->status()`）。
13. **Tailwind v4 主题**：Filament 4+ 需自定义主题（`make:filament-theme`）并补 `@source`；或使用预编译 CSS。
14. **`Filament\Pages\Auth\Login`（AutoFillLogin）**：v5 仍存在，确认 `->form->fill()` 用法。

### 4.3 环境要求
- Filament 5 要求：PHP 8.2+、Laravel 11.28+、**Livewire v4**、Tailwind v4.1+（仅自定义主题/独立 CSS 时需要）。
- 主项目 LaFiDemo（Laravel 13 + Filament 5）满足 Laravel/PHP 要求，但需确认 Livewire v4 与前端构建（Vite）。

## 5. 校验结果
- 集成后 `app/Filament/Admin`：**113** 个 PHP 文件；无残留 `Tables\Actions\`、无残留旧 `App\Domains\` 等前缀引用。
- `headerActions()` 已应用 58 处；`->reactive()` 已清零。
- `App\Filament\*` 内部引用保持一致（`Pages`、`Auth`、`Concerns`、导出器）。
