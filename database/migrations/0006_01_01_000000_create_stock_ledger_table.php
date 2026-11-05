<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory 模块基线表：stock_ledger（blueprint §5.3 inventory_movements 的
 * 只追加流水账本，表名取任务基线口径 stock_ledger）。
 *
 * 审计事实表（data-and-cache.md §1/§2、architecture.md §5）：
 * - 只追加：流水不可更新/删除，禁止普通级联删除抹除库存账目；
 * - quantity_delta 为有符号整数（in 正 / out 负），before/after 供对账复算；
 * - 幂等键作用域 = 租户 + 业务事件键：UNIQUE (tenant_id, idempotency_key)，
 *   数据库唯一约束是防重的最后防线（"应用层先查不能替代唯一约束"）；
 * - 审计字段：who（operator_type/operator_id）、when（created_at，UTC）、
 *   why（reason）、source ref（reference_type/reference_id）。
 * variant_id 采用 restrict：流水存在的 variant 不可被普通删除抹除。
 * 纯新增表（expand-and-contract：expand 阶段），无旧数据迁移。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('variant_id');
            // 受控取值：'in' / 'out'（应用层经 StockMovementType 枚举校验）
            $table->string('movement_type', 16);
            // 有符号：in 为正、out 为负（UNSIGNED 无法表达负向冲销）
            $table->integer('quantity_delta');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('idempotency_key', 128);
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            // who：操作者类型（'user' / 'system'）与 ID（基线允许 system 来源）
            $table->string('operator_type', 32)->default('system');
            $table->unsignedBigInteger('operator_id')->nullable();
            // why：调整原因（应用层强制非空）
            $table->string('reason', 500);
            $table->timestamp('created_at')->useCurrent();

            // 幂等键作用域 = 租户 + 业务事件键（data-and-cache.md §2）
            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'variant_id', 'created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // 流水不可因 variant 普通删除被抹除（审计事实保护）
            $table->foreign('variant_id')->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
