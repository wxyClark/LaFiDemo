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

### [adr/](adr/)

架构决策记录（ADR），记录重大技术取舍、不可逆数据模型与跨模块依赖的背景、备选、决策与复核条件：

| 文档 | 内容 |
| --- | --- |
| [adr/TEMPLATE.md](adr/TEMPLATE.md) | ADR 模板：章节结构与状态流转（Proposed → Accepted → Deprecated/Superseded）的唯一出处，新建 ADR 复制填写 |
| [adr/0001-module-structure.md](adr/0001-module-structure.md) | ADR-0001 模块目录结构与跨模块调用规则（Proposed） |

## 开发规范

入口：[rules/README.md](rules/README.md)（规范总览、强制级别与适用范围）。各专项规范：

| 文档 | 内容 |
| --- | --- |
| [rules/coding-standards.md](rules/coding-standards.md) | PHP 8.4+、Laravel 13、模块边界、Filament、API 约定 |
| [rules/application-flow-and-data-access.md](rules/application-flow-and-data-access.md) | 应用调用链路、输出转换、共享代码、Service/定时任务、Repository/批处理、基类和字典表取舍 |
| [rules/data-and-cache.md](rules/data-and-cache.md) | MySQL 8.4、Redis、事务、索引、库存一致性与队列 |
| [rules/testing-security.md](rules/testing-security.md) | Pest、质量门禁、测试/迁移数据库引擎一致性、门禁命令单一事实来源、安全审查放行、secret 扫描、租户隔离和隐私 |
| [rules/delivery-and-operations.md](rules/delivery-and-operations.md) | Git/PR、审查严重度分级、ADR 规范（引用 `adr/TEMPLATE.md`）、CI、迁移发布、可观测性、备份与回滚 |

## 评审与决策记录

阶段性评审与风险清单（只读文档评审，不含代码/流水线改动）：

| 文档 | 内容 |
| --- | --- |
| [reviews/architecture-review.md](reviews/architecture-review.md) | 基础架构评审：现状盘点、风险与缺口清单（R-01…R-10）与一致性修复建议（F-1…F-6） |
| [reviews/prompt-library-review.md](reviews/prompt-library-review.md) | 提示词库评估：模板与流程阶段映射、缺口矩阵、使用顺序与模板质量发现 |

ADR 目录见上方 [adr/](adr/) 小节（TEMPLATE.md 为 ADR 章节结构唯一模板）。

## AI 辅助开发

入口：[prompts/README.md](prompts/README.md)。相关文档：

| 文档 | 内容 |
| --- | --- |
| [prompts/workflow.md](prompts/workflow.md) | 推荐开发全流程：阶段门禁、责任边界、分支策略与完成定义 |
| [prompts/prompt-library.md](prompts/prompt-library.md) | AI 提示词模板库（需求分析、PRD、设计、评审、编码、测试、发布） |
