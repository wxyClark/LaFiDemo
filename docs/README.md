# 文档导航

本目录是 LaFiDemo 的全部项目文档入口。开始任何开发任务前，先阅读根目录 [`AGENTS.md`](../AGENTS.md)（全局强制规则），再按本索引查阅对应文档。

## 架构与技术选型

| 文档 | 内容 |
| --- | --- |
| [architecture-blueprint.md](architecture-blueprint.md) | 系统架构图、模块目录与数据库表结构初稿 |
| [technology-selection.md](technology-selection.md) | 技术选型：总览、对比与版本决策 |

### [architecture/](architecture/)

架构决策与实施蓝图，包含：

| 文档 | 内容 |
| --- | --- |
| [architecture/architecture.md](architecture/architecture.md) | 架构决策、领域模型、约定、示例与实施路线 |
| [architecture/architecture-components.md](architecture/architecture-components.md) | 组件与 PHP 运行时决策（PHP 8.4 基线、CVE-2024-4577 安全要求、基础 Composer 组件约束） |
| [architecture/ecommerce-platform-blueprint.md](architecture/ecommerce-platform-blueprint.md) | 电商 SaaS 系统总体方案与实施路线（多租户、库存、支付、O2O/分销） |

## 开发规范

入口：[rules/README.md](rules/README.md)（规范总览、强制级别与适用范围）。各专项规范：

| 文档 | 内容 |
| --- | --- |
| [rules/coding-standards.md](rules/coding-standards.md) | PHP 8.4+、Laravel 13、模块边界、Filament、API 约定 |
| [rules/application-flow-and-data-access.md](rules/application-flow-and-data-access.md) | 应用调用链路、输出转换、共享代码、Service/定时任务、Repository/批处理、基类和字典表取舍 |
| [rules/data-and-cache.md](rules/data-and-cache.md) | MySQL 8.4、Redis、事务、索引、库存一致性与队列 |
| [rules/testing-security.md](rules/testing-security.md) | Pest、质量门禁、安全、租户隔离和隐私 |
| [rules/delivery-and-operations.md](rules/delivery-and-operations.md) | Git/PR、CI、迁移发布、可观测性、备份与回滚 |

## AI 辅助开发

入口：[prompts/README.md](prompts/README.md)。相关文档：

| 文档 | 内容 |
| --- | --- |
| [prompts/workflow.md](prompts/workflow.md) | 推荐开发全流程：阶段门禁、责任边界、分支策略与完成定义 |
| [prompts/prompt-library.md](prompts/prompt-library.md) | AI 提示词模板库（需求分析、PRD、设计、评审、编码、测试、发布） |
