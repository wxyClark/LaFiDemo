<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog 模块基线表：product_variants（blueprint §5.2）。
 * SKU 是库存单位（blueprint §5.2 / architecture.md §5）：
 * - 租户组合唯一键 (tenant_id, sku)（blueprint §3.2 / data-and-cache.md §1）；
 * - (tenant_id, id) 复合唯一防跨租户 ID 引用。
 * 纯新增表（expand-and-contract：expand 阶段），无旧数据迁移。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 64);
            // 受控状态：'active' / 'disabled'
            $table->string('status', 20)->default('active');
            // 低频可变规格属性（blueprint §3.1：JSON 仅用于低频、结构可变字段）
            $table->json('attributes_json')->nullable();
            $table->string('barcode', 64)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'sku']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // 同租户关联（blueprint §3.2 / data-and-cache.md §1：外键尽可能保证同租户关联；
            // MySQL 暂不支持复合外键，"同租户"部分由应用校验 + 跨租户写入测试兜底，见
            // ProductVariantQueries::variantBelongsTo 与库存调整用例的归属检查）。
            // variants 随 products 删除级联；库存流水引用 variant_id 时采用 restrict
            // （见 stock_ledger 迁移），禁止抹除审计事实
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
