# 开发规范索引

本文档集为 LaFiDemo 项目的工程约定，适用于代码、测试、数据结构、管理后台、API、部署及协作流程。规范参考 PHP、Laravel、Filament、MySQL、Redis、Pest 官方文档中的通用最佳实践，并结合电商 SaaS 的多租户、订单、库存、支付和审计风险制定。

> “国内大厂规范”并非单一公开标准。本规范吸收成熟团队常见工程实践（代码审查、最小权限、可观测性、灰度/回滚、兼容性、自动化质量门禁），不声称代表任何特定公司的内部制度。官方框架/API 的当前文档优先于本文中可能过时的版本细节。

## 文件

- [`coding-standards.md`](coding-standards.md)：PHP 8.3、Laravel 13、模块边界、Filament、API 约定。
- [`data-and-cache.md`](data-and-cache.md)：MySQL 8.4、Redis、事务、索引、库存一致性与队列。
- [`testing-security.md`](testing-security.md)：Pest、质量门禁、安全、租户隔离和隐私。
- [`delivery-and-operations.md`](delivery-and-operations.md)：Git/PR、CI、迁移发布、可观测性、备份与回滚。

仓库根目录 [`AGENTS.md`](../../AGENTS.md) 是面向所有贡献者和自动化编码代理的全局摘要。它要求先遵循此处规范，并确保测试/检查结果如实报告。

## 强制级别

- **MUST / 必须**：合并前必须满足；仅可通过明确 ADR、风险评估和负责人批准偏离。
- **SHOULD / 应当**：默认执行；不执行时需在 PR 解释替代方案。
- **MAY / 可选**：基于需求和成本采用，不是当前强制依赖。

## 适用与更新

开始任务先阅读根目录 `AGENTS.md` 和相关专项文档。规范随项目成熟迭代；规则变更需代码评审，并同步更新示例、CI 或模板，避免文档与实际检查脱节。依赖版本以 `composer.lock`、镜像摘要和 CI 配置为准，不在规范中复制动态版本号。
