<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog 模块基线表（ADR-0001 d 条：迁移保留在 database/migrations/，
 * 模块归属体现在命名与注释；blueprint §6 顺序 3 的基线子集）。
 *
 * products 为商品 SPU（blueprint §5.2）：租户维度唯一 slug；
 * (tenant_id, id) 复合唯一防跨租户 ID 引用（blueprint §3.2 方向）。
 * 纯新增表（expand-and-contract：expand 阶段），无旧数据迁移。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('slug', 100);
            $table->string('title', 200);
            $table->text('description')->nullable();
            // 受控状态：'active' / 'disabled'（应用层枚举校验）
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'slug']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
