# 薪福通 API 对接模块

招商银行薪福通 (Xinfutong) 开放平台 API 对接模块，基于 Laravel + Filament 实现。

## 平台简介

薪福通是招商银行旗下平台级 SaaS 产品，一站式人财事数字开放平台。官网：https://xft.cmbchina.com/open

## 模块功能

- **员工信息同步** - 从薪福通拉取员工基本信息并同步到本地数据库
- **审批流提单** - 通过 API 发起审批流程
- **同步日志** - 记录所有同步操作的历史

## 环境要求

- PHP 8.0+（需要 GMP 或 OpenSSL 扩展支持国密算法）
- OpenSSL 1.1.1+（支持 SM2/SM3/SM4）
- Laravel 10+

## 配置说明

### 1. 获取 API 凭证

1. 访问薪福通开放平台：https://xft.cmbchina.com/open
2. 注册并登录开发者账号
3. 创建应用，获取以下凭证：
   - `AppId` - 应用 ID
   - `AuthoritySecret` - 权限密钥
   - `SignPrivateKey` - SM2 签名私钥
   - `VerifyPublicKey` - SM2 验签公钥

### 2. 配置文件

配置文件位于 `config/xinfutong.php`，支持多组账号配置。

### 3. 环境变量配置

在 `.env` 文件中添加以下配置：

```env
# 默认账号配置
XINFUTONG_DEFAULT_ACCOUNT=default
XINFUTONG_BASE_URL=https://xft.cmbchina.com/open
XINFUTONG_APP_ID=your_app_id_here
XINFUTONG_AUTHORITY_SECRET=your_authority_secret_here
XINFUTONG_SIGN_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
your_sm2_private_key_here
-----END PRIVATE KEY-----"
XINFUTONG_VERIFY_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
your_sm2_public_key_here
-----END PUBLIC KEY-----"
XINFUTONG_TIMEOUT=30
```

### 4. 多账号配置

支持配置多组账号，用于不同环境或业务场景：

```php
// config/xinfutong.php
'accounts' => [
    'default' => [
        'base_url' => env('XINFUTONG_BASE_URL', 'https://xft.cmbchina.com/open'),
        'app_id' => env('XINFUTONG_APP_ID', ''),
        // ...
    ],
    'production' => [
        'base_url' => env('XINFUTONG_PROD_BASE_URL', 'https://xft.cmbchina.com/open'),
        'app_id' => env('XINFUTONG_PROD_APP_ID', ''),
        // ...
    ],
    'testing' => [
        'base_url' => env('XINFUTONG_TEST_BASE_URL', 'https://xft-demo.cmburl.cn/open'),
        'app_id' => env('XINFUTONG_TEST_APP_ID', ''),
        // ...
    ],
],
```

使用指定账号：

```php
// 使用默认账号
$service = app(XinfutongService::class);

// 使用指定账号
$service = new XinfutongService('production');
$service = new XinfutongService('testing');
```

### 5. 配置参数说明

| 参数 | 说明 | 必填 |
|------|------|------|
| `XINFUTONG_DEFAULT_ACCOUNT` | 默认账号名称 | 否，默认 'default' |
| `XINFUTONG_BASE_URL` | API 基础地址 | 是 |
| `XINFUTONG_APP_ID` | 应用 ID | 是 |
| `XINFUTONG_AUTHORITY_SECRET` | 权限密钥 | 是 |
| `XINFUTONG_SIGN_PRIVATE_KEY` | SM2 签名私钥（PEM 格式） | 是 |
| `XINFUTONG_VERIFY_PUBLIC_KEY` | SM2 验签公钥（PEM 格式） | 否 |
| `XINFUTONG_TIMEOUT` | API 请求超时时间（秒） | 否，默认 30 |

## API 签名机制

薪福通 API 使用国密 SM2/SM3 算法进行请求签名：

### 签名流程

1. 生成毫秒级时间戳 (`timestamp`)
2. 生成 32 字节随机数 (`nonce`)
3. 构造请求体，包含 `appId`、`timestamp`、`nonce` 和业务参数
4. 将 `appId + timestamp + nonce + body` 拼接为待签名字符串
5. 使用 SM3 算法计算哈希值
6. 使用 SM2 私钥对哈希值进行签名
7. 将签名结果 Base64 编码后放入请求头 `Signature`

### 请求头

```http
Content-Type: application/json
Accept: application/json
AppId: your_app_id
Timestamp: 1234567890123
Nonce: abc123def456...
Signature: base64_encoded_signature
```

## 数据库表结构

### xinfutong_employees（员工表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| xinfutong_id | varchar(64) | 薪福通员工 ID（唯一） |
| employee_no | varchar(64) | 工号 |
| name | varchar(100) | 姓名 |
| gender | varchar(10) | 性别 |
| phone | varchar(20) | 手机号 |
| email | varchar(100) | 邮箱 |
| department | varchar(100) | 部门 |
| position | varchar(100) | 职位 |
| hire_date | date | 入职日期 |
| status | varchar(20) | 状态 |
| raw_data | json | 原始 API 数据 |
| last_synced_at | datetime | 最后同步时间 |

### xinfutong_sync_logs（同步日志表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| employee_id | bigint | 关联员工 |
| sync_type | varchar(20) | 同步类型 |
| status | varchar(20) | 同步状态 |
| records_fetched | int | 拉取记录数 |
| records_created | int | 新增记录数 |
| records_updated | int | 更新记录数 |
| error_message | text | 错误信息 |
| started_at | datetime | 开始时间 |
| completed_at | datetime | 完成时间 |

### xinfutong_approval_requests（审批请求表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| approval_no | varchar(64) | 审批单号（唯一） |
| approval_type | varchar(50) | 审批类型 |
| title | varchar(200) | 审批标题 |
| description | text | 审批说明 |
| form_data | json | 表单数据 |
| status | varchar(20) | 审批状态 |
| applicant_name | varchar(100) | 申请人姓名 |
| applicant_id | varchar(64) | 申请人员工 ID |
| xinfutong_approval_id | varchar(64) | 薪福通审批单 ID |
| error_message | text | 错误信息 |
| raw_response | json | 原始 API 响应 |
| submitted_at | datetime | 提交时间 |
| approved_at | datetime | 审批完成时间 |

## 使用方法

### 1. 运行数据库迁移

```bash
php artisan migrate
```

### 2. 访问管理页面

- **员工管理**: `/admin/xinfutong-employees`
- **同步日志**: `/admin/xinfutong-sync-logs`
- **审批提单**: `/admin/xinfutong-approvals`

### 3. 同步员工数据

在员工管理页面点击「同步数据」按钮，将从薪福通 API 拉取最新员工信息。

### 4. 提交审批

在审批提单页面创建审批单，填写相关信息后点击「提交审批」。

## API 端点说明

### 当前实现

| 功能 | 端点 | 说明 |
|------|------|------|
| 员工列表 | `/api/v1/employee/list` | 获取员工列表 |
| 发起审批 | `/api/v1/approval/submit` | 提交审批申请 |
| 审批状态 | `/api/v1/approval/status` | 查询审批状态 |

### 待实现

| 功能 | 说明 |
|------|------|
| 组织架构 | 获取组织架构列表 |
| 薪资数据 | 获取薪资明细 |
| 考勤数据 | 获取考勤记录 |
| 社保公积金 | 获取社保公积金数据 |

## 错误处理

### 常见错误

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| 401 | 认证失败 | 检查 AppId 和 AuthoritySecret 配置 |
| 403 | 权限不足 | 确认应用已开通对应接口权限 |
| 500 | 服务器错误 | 稍后重试或联系技术支持 |

### 日志查看

同步错误日志可通过以下方式查看：

```bash
# 查看 Laravel 日志
tail -f storage/logs/laravel.log

# 或在同步日志页面查看
/admin/xinfutong-sync-logs
```

## 开发说明

### 代码结构

```
app/Domains/Xinfutong/
├── Enums/                    # 枚举类
│   ├── ApprovalStatus.php
│   ├── ApprovalType.php
│   ├── EmployeeStatus.php
│   └── SyncStatus.php
├── Models/                   # 模型
│   ├── ApprovalRequest.php
│   ├── Employee.php
│   └── SyncLog.php
├── Services/                 # 服务
│   └── XinfutongService.php  # 核心 API 服务
├── Database/Factories/       # 工厂
│   ├── ApprovalRequestFactory.php
│   ├── EmployeeFactory.php
│   └── SyncLogFactory.php
└── README.md                 # 本文档
```

### 扩展新功能

1. 在 `XinfutongService` 中添加新的 API 调用方法
2. 创建对应的 Model（如需要）
3. 在 `config/services.php` 中添加配置
4. 创建 Filament Resource 页面

### 国密加密说明

当前实现使用 OpenSSL 的 SM2/SM3 支持。如果 OpenSSL 版本不支持，会回退到 RSA-SHA256。

**生产环境建议**：
1. 确保 PHP OpenSSL 扩展支持 SM2/SM3
2. 或安装 `pohoc/crypto-sm` 等国密库

## 相关文档

- [薪福通开放平台文档](https://xft.cmbchina.com/open/#/doc)
- [招商银行 Open API](https://open.cmbchina.com)
- [国密算法简介](https://baike.baidu.com/item/SM2)

## License

MIT License
