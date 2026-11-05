# PHP 运行时与 Composer 基础组件决策

> 状态：项目基线决策（2026-11-03）。本文件补充 [`architecture.md`](architecture.md) 和 [`../architecture-blueprint.md`](../architecture-blueprint.md) 的运行时与基础组件选型。Laravel 应用骨架与 `composer.lock` 已随 init 提交落地，`composer.json` 声明依赖边界且已通过 Composer 解析锁定；后续组件新增仍以本文件为唯一出处。

## 1. PHP 版本决策

### 结论

- 项目最低 PHP 版本：**8.4.3**（Composer 约束 `^8.4.3`）。
- 推荐生产运行时：**PHP 8.4 分支最新可用安全补丁版**，并固定到受控容器镜像摘要/发行包；不得把 8.4.3 当作生产补丁目标，它只是最低安全/兼容基线。
- 本项目不以 PHP 8.3 作为部署运行时。虽然 Laravel 13 本身接受 PHP `^8.3`，本项目选择 OpenSpout 等组件后整体最低要求提高到 PHP 8.4；减少双运行时差异，也能使用组件当前维护线。
- 2026-11-03 依赖解析修订：Filament 5 的 `filament/actions` 传递锁定 `openspout/openspout ^4.23`，与 5.x 主版本互斥，根约束由 `^5.13` 对齐为 `^4.23`（流式 CSV/XLSX 导出能力不变，5.x 相对 4.x 无本项目所需的 API 增益；后续如 Filament 升级支持 5.x 再回迁）。
- CI 首先验证与生产一致的 PHP 8.4 最新补丁环境。只有当团队确实需要支持 8.3 的其他应用包时，才单独评估兼容分支；不得为此降低本项目根 Composer 约束或放宽安全要求。

### PHP2Shell / CVE-2024-4577 安全要求

PHP2Shell 通常指 CVE-2024-4577 PHP-CGI 参数注入漏洞。PHP 官方修复版本的最低点包括 PHP 8.3.8、8.2.20、8.1.29；PHP 8.4 分支从首次安全修复版本 8.4.3 起包含修复。**版本达到修复下限仅表示包含该漏洞修复，并不表示当前运行时仍受支持或具备最新安全更新。**

执行要求：

1. 生产和预发布使用 PHP 8.4 当前最新安全补丁；定期更新镜像并重新构建、扫描和测试。
2. 禁止在生产使用 PHP 8.4.0–8.4.2。`composer.json` 的 `^8.4.3` 是最低依赖约束，不能代替部署镜像补丁策略。
3. Linux 上优先使用受支持的 PHP-FPM + Nginx/Apache FastCGI 部署，并限制 CGI/FastCGI 参数传递；不要将未经核查的 CGI 配置暴露到公网。
4. 若因兼容要求运行 Windows PHP-CGI，必须逐项执行 PHP 官方安全公告中的修复和系统区域设置缓解措施，验证实际 SAPI、Web Server 参数映射并安排更新；不能只依据 `php -v` 判定安全。
5. 依赖中的 `composer.json` PHP 约束不能识别某一补丁级 CVE。安全门禁还需扫描运行时镜像/SBOM，并监测 PHP 安全公告。

参考：[PHP 官方 CVE-2024-4577 公告](https://www.php.net/ChangeLog-8.php#8.3.8)、[NVD CVE-2024-4577](https://nvd.nist.gov/vuln/detail/CVE-2024-4577)、[PHP 支持版本](https://www.php.net/supported-versions.php)。部署时复核最新公告和补丁。

## 2. Composer 依赖基线

根目录新增 `composer.json`，列出项目第一阶段确认的运行时及开发依赖约束。实际解析后必须提交 Composer 生成的 `composer.lock`；此文件未生成前不得宣称包版本组合已验证。

| 需求 | 选择 | 加入方式/范围 | 思考与边界 |
| --- | --- | --- | --- |
| Web/应用框架 | `laravel/framework ^13.0` | 运行时 | API、数据库、队列、缓存、验证、授权、迁移和文件系统的主框架。 |
| 后台面板/RBAC | `filament/filament ^5.0` + Laravel Policy/Gate | 运行时 | 先用框架原生 Policy/Gate 和 membership 角色关联实现授权边界；Filament UI 隐藏不是授权。若 RBAC 管理需求变复杂，再评估权限包并检查 Laravel 13 兼容，不因缩写 RBAC 预先引入未验证插件。 |
| 会话/API 认证 | `laravel/sanctum ^4.0` | 运行时 | 第一方客户端认证；身份认证与租户/资源授权分离。 |
| 异步任务运维 | `laravel/horizon ^5.50` | 运行时 | Redis Queue 的管理与观测；可靠业务事件仍需 Outbox 和幂等消费。 |
| Excel/XLSX 与 CSV | `openspout/openspout ^4.23` | 运行时 | 流式读写，适合大数据量导出且内存占用更可控。CSV 可以流式生成；需复杂公式/模板/宏时再专项评估 PhpSpreadsheet/Maatwebsite Excel。导出应后台队列化，限定租户范围，防止 CSV formula injection，使用安全文件名和短期下载链接。版本与 Filament 5 传递约束（^4.23）对齐，见 §1 依赖解析修订。 |
| PDF | `barryvdh/laravel-dompdf ^3.1` | 运行时 | Laravel 集成和 HTML 模板开发方便，适用于订单/简单报表。中文字体、远程资源、SVG 和复杂分页需验证；关闭不必要远程资源访问、限制本地文件根目录和输入内容。高并发/复杂排版可另评估独立渲染服务。 |
| 微信支付 + 支付宝 | `yansongda/pay ^3.7.20` | 运行时 | 单一渠道适配 SDK 覆盖常见微信支付/支付宝流程，减少双 SDK 和重复验签逻辑。将 SDK 包装在项目 `Payment` 模块的统一 adapter 后；支付签名、通知验签、金额币种校验、幂等、退款与沙箱测试仍由应用规则把关。最低采用包含已知安全修复的版本，安装时仍需 `composer audit`。 |
| 二维码生成 | `chillerlan/php-qrcode ^5.0` | 运行时 | 用于支付/自提核销等 QR 内容生成；二维码只承载短期 token/URL，不放个人信息或长期可复用密钥。服务端保存 token 哈希、设置有效期、限流并校验权限。二维码不是授权本身。 |
| 图片处理/生成 | `intervention/image ^4.0` + PHP GD 扩展 | 运行时 | 用于商品图缩放、裁切、格式转换、基础合成/水印；安装后核验 GD codec 支持。复杂设计模板、海报排版和高负载任务在队列执行，并限制输入大小、像素和处理时长。 |
| 财务金额计算 | `brick/money ^0.15`（依赖精确数学库） | 运行时 | Money/Currency 类型避免浮点误差，支持明确币种及舍入策略。持久化仍按最小单位整数 + currency code；折扣分摊、税、佣金舍入规则必须由业务确认并测试。 |
| 唯一 ID | Laravel `Str::ulid()` / UUID helper + 数据库唯一约束 | Laravel 内建，不另加包 | 内部主键可统一选择 ULID（排序友好、较难枚举但不等于授权）。订单号、支付单号、SKU 是不同业务编号，需要各自格式/范围和唯一索引；冲突时有限重试。随机 ID 不能代替唯一约束，也不要把订单主键直接暴露为权限。 |
| Redis 客户端 | 优先 `ext-redis` / PhpRedis | PHP 扩展 | Laravel 原生支持。按部署环境检查扩展版本、TLS、持久化和连接限制；若无法装扩展再评估 Predis，避免并行维护两种客户端。 |
| 代码质量/测试 | Pest 4、Pest Laravel 插件 4、Pint 1、Larastan 3 | 开发依赖 | 版本约束与 PHP 8.4 / Laravel 13 兼容性需实际求解；CI 使用真实 MySQL/Redis 验证交易与并发语义。 |

### 不应重复引入的能力

- 不再额外添加 `overtrue/laravel-wechat` 作为第二套微信支付通道，除非明确使用其非支付微信能力；支付只通过 Payment adapter 暴露。
- 暂不增加 Maatwebsite Excel/PhpSpreadsheet、多个 QR 包或另一个 UUID 包；出现明确格式/功能缺口再评估，避免重叠依赖。
- “RBAC”是授权模型，不是二维码组件。首版用 Laravel Policy/Gate + 租户成员/角色关系；如果以后引入 Spatie 等 RBAC 包，先验证支持 Laravel 13 的发布线、插件安全和迁移成本。
- 不引入浮点金额库或用 Decimal 字符串进行未定义精度的手工运算；金额运算规则集中在 Money/Price/Promotion 域服务。

## 3. Composer 与运行环境约定

- `composer.json` 中的范围是允许解析的版本区间，不代表安装了某个具体版本。初始化应用后运行 Composer 解析并提交 `composer.lock`；部署只用锁文件执行 `composer install`。
- 当前 repository 还没有 Laravel 入口、`artisan`、`bootstrap/app.php` 等完整骨架。先生成 Laravel 13 skeleton，再验证 Composer scripts、Filament 安装、包发现和 migrations；此时不在 Composer post-install hook 假设 `artisan` 已存在。
- 生产扩展至少检查 Composer 声明的 `bcmath`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `redis`, `xml`, `zip` 相关模块；容器构建和 `composer check-platform-reqs` 需与实际运行镜像一致。
- 环境变量中不得存密钥明文到版本库。微信商户证书/私钥、支付宝应用私钥和平台公钥通过 Secret 管理/只读挂载提供，按租户隔离并实施轮换；不要把敏感支付密钥放在 `config_json` 或数据库普通字段。
- 在引入每个包时检查维护状态、许可证、PHP/Laravel 兼容、`composer audit` 和安全公告。具体补丁版本由 lock 文件固定，安全升级优先于普通 feature。

## 4. 初始化验收

形成可运行 Laravel 项目后，需完成并记录：

1. `composer validate --strict`、依赖解析和 `composer audit`；解决所有冲突和高风险公告。
2. `composer.lock` 已提交；使用 PHP 8.4 最新补丁安装，`composer check-platform-reqs` 与生产容器一致。
3. Laravel/Filament 双后台启动；访问策略和 TenantContext 有测试。
4. 真实 MySQL 8.4 与 Redis 环境通过 migration、队列和库存并发测试。
5. OpenSpout CSV/XLSX、Dompdf 中文字体、QR 输出、Intervention Image GD codec、Brick Money 多币种舍入有最小验证用例。
6. 支付接沙箱；测试签名/通知/重复回调/退款/幂等。未完成商户资质、真实回调域名与密钥评审前，不启用生产支付。
7. PHP 镜像/SAPI 和补丁扫描确认不存在已知 PHP-CGI 风险；部署文档说明受支持运行方式与更新负责人。
