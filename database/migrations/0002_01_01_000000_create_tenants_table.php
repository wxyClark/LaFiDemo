<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenancy 模块基线表（ADR-0001 拟议决策 d 条：迁移保留在 database/migrations/，
 * 模块归属体现在命名与注释；blueprint §6 顺序 1）。
 *
 * 纯新增表，无旧数据迁移（expand-and-contract：expand 阶段）。
 * users / tenant_memberships 归属 Tenancy 模块首任务细化（ADR-0001 后果节），
 * 本基线不动骨架 users 表。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            // 受控状态，应用层经 TenantStatus 枚举校验取值；'active' / 'disabled'
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
