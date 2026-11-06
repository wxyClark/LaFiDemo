<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 基线：users 加 role 与 tenant_id（expand-only，向前兼容）。
 *
 * 说明（ADR-0001 / 架构评审遗留项 1-2）：
 * - users 表归属（留骨架 vs 并入 Tenancy）尚未裁决，本次保持骨架归属，
 *   仅按双 Panel 基线最小需要加列；
 * - role：平台管理员（platform）/ 租户用户（tenant），本基线用简单列承载，
 *   RBAC/membership 落地后由 memberships/roles 表承接，此列退役；
 * - tenant_id：租户用户归属的租户，成员关系落地后由 tenant_memberships
 *   解析取代（TenantContextResolver 注释已约定），此列退役。
 * - 退役计划：membership 模块落地后，先 expand 加 memberships 表并双写，
 *   再 contract 移除本列（docs/rules/delivery-and-operations.md §3）。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('tenant')->after('email');
            $table->unsignedBigInteger('tenant_id')->nullable()->after('role');
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['role', 'tenant_id']);
        });
    }
};
