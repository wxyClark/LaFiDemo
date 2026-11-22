<?php

declare(strict_types=1);

namespace App\Modules\Dev\Services;

use App\Modules\Dev\Enums\User\RoleNameEnums;
use App\Modules\Domains\Auth\Models\Admin;
use Illuminate\Database\Eloquent\Builder;

/**
 * 权限判定（基于 spatie/laravel-permission）
 *
 * @author wxyClark
 *
 * @create 2025/11/29 15:26
 *
 * @version 1.0
 */
class PermissionService
{
    /**
     * 判定用户是否具有管理员角色
     *
     * 原实现依赖 Dev 自建的 `roles` / `role_user_relations`（无迁移），已迁至 Spatie 权限体系。
     */
    public function getIsAdmin(int|string|null $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        return Admin::query()
            ->whereKey($userId)
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', RoleNameEnums::ADMIN))
            ->exists();
    }
}
