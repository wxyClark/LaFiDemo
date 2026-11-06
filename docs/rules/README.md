# 开发规范索引

本文档集为 LaFiDemo 项目的工程约定，适用于代码、测试、数据结构、管理后台、API、部署及协作流程。规范参考 PHP、Laravel、Filament、MySQL、Redis、Pest 等官方文档和成熟工程实践，并按项目架构做取舍。

> “国内大厂规范”并非单一公开标准。本规范吸收成熟团队常见工程实践（代码审查、最小权限、可观测性、灰度/回滚、兼容性、自动化质量门禁等），不声称复制某一家公司的内部规范。

## 文件

- [`coding-standards.md`](coding-standards.md)：PHP 8.4+、Laravel 13、模块边界、Filament、API 约定、注释规范、命名专项。
- [`application-flow-and-data-access.md`](application-flow-and-data-access.md)：应用调用链路、输出转换、共享代码、Service/定时任务、Repository/批处理、基类和字典表取舍。
- [`data-and-cache.md`](data-and-cache.md)：MySQL 8.4、Redis、事务、索引、库存一致性与队列、数据表常用字段基线。
- [`testing-security.md`](testing-security.md)：Pest、质量门禁、测试/迁移数据库引擎一致性、门禁命令单一事实来源、安全审查放行、secret 扫描、租户隔离和隐私。
- [`delivery-and-operations.md`](delivery-and-operations.md)：Git/PR、多分支并行与冲突处理、审查严重度分级、ADR 规范（引用 `docs/adr/TEMPLATE.md`）、CI、迁移发布、可观测性、备份与回滚。
- [`../architecture/architecture-components.md`](../architecture/architecture-components.md)：PHP 8.4 运行时、CVE-2024-4577 安全要求和基础 Composer 组件决策。
- [`../architecture/ecommerce-platform-blueprint.md`](../architecture/ecommerce-platform-blueprint.md)：电商 SaaS 系统总体方案与实施路线。

仓库根目录 [`AGENTS.md`](../../AGENTS.md) 是面向所有贡献者和自动化编码代理的全局摘要。它要求先遵循此处规范，并确保测试/检查结果如实报告。

## 强制级别

- **MUST / 必须**：合并前必须满足；仅可通过明确 ADR、风险评估和负责人批准偏离。
- **SHOULD / 应当**：默认执行；不执行时需在 PR 解释替代方案。
- **MAY / 可选**：基于需求和成本采用，不是当前强制依赖。

## 适用与更新

开始任务先阅读根目录 `AGENTS.md` 和相关 `docs/rules/`、架构及技术选型文档。规范随项目成熟迭代；规则变更需代码评审，并同步更新示例、CI 或模板，避免文档与实际检查脱节。
