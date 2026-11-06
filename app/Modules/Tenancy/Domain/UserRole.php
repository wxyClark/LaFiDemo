<?php

namespace App\Modules\Tenancy\Domain;

/**
 * 用户角色基线（双 Panel：平台运营 vs 租户商家）。
 *
 * 模块归属：Tenancy（角色/成员关系属租户域，ADR-0001 a 条）。
 * 本基线为最小角色区分（平台管理员 / 租户用户），RBAC/membership 落地后
 * 由 memberships + roles 表承接，本枚举随 users.role 列一并退役
 * （见 0007 迁移头注释与 TenantContextResolver 注释约定）。
 */
enum UserRole: string
{
    /** 平台运营管理员：仅可访问 Platform Panel，管理租户（TenantPolicy 平台分支）。 */
    case Platform = 'platform';

    /** 租户商家用户：可访问 Tenant Panel，业务范围限定在所属租户（TenantContext）。 */
    case Tenant = 'tenant';
}
