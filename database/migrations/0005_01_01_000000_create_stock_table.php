<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory 模块基线表：stock（blueprint §5.3 inventory_balances 的基线读模型）。
 *
 * 库存余额是可快速读取的投影，正确性由条件更新/行锁与只追加流水（stock_ledger）
 * 保障（data-and-cache.md §1/§2）：
 * - 数量一律 UNSIGNED INT（SKU 为整件库存单位，禁止浮点；architecture.md §5）；
 * - version 供条件更新/乐观并发控制（后续并发测试基线）；
 * - (tenant_id, variant_id) 唯一：基线默认主仓维度；后续 Store 模块引入仓库后
 *   经 expand 迁移增加 warehouse_id 列并扩唯一键。
 * reserved_qty / reservations 属库存预占后续模块（blueprint §5.3），本次不建。
 * 纯新增表（expand-and-contract：expand 阶段），无旧数据迁移。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedInteger('on_hand')->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'variant_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // 库存流水是审计事实：variant 被删除前必须先清理流水（restrict），
            // 禁止普通删除级联抹除库存账目（data-and-cache.md §1）
            $table->foreign('variant_id')->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
