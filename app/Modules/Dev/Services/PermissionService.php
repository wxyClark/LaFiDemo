<?php

namespace App\Modules\Dev\Services;

use App\Modules\Dev\Enums\User\RoleNameEnums;
use App\Modules\Dev\Enums\User\RoleStatusEnums;
use App\Modules\Dev\Repositories\User\RoleRepository;
use App\Modules\Dev\Repositories\User\RoleUserRelationRepository;

/**
 * Created by PhpStorm
 * Author: wxyClark
 * Date: 2025/11/29
 * Time: 11:27
 * Email: C18666211369@outlook.com
 */
class PermissionService
{
    /** @var RoleRepository */
    protected $roleRepository;

    /** @var RoleUserRelationRepository */
    protected $userRoleRelationRepository;

    public function __construct(
        RoleRepository $roleRepository,
        RoleUserRelationRepository $userRoleRelationRepository
    ) {
        $this->roleRepository = $roleRepository;
        $this->userRoleRelationRepository = $userRoleRelationRepository;
    }

    /**
     * 判定用户是否有管理员权限
     *
     * @author wxyClark
     *
     * @create 2025/11/29 15:26
     *
     * @version 1.0
     */
    public function getIsAdmin($userId): bool
    {
        $roleId = $this->roleRepository->one([
            'name' => RoleNameEnums::ADMIN,
            'status' => RoleStatusEnums::ROLE_STATUS_ENABLE,
        ]);
        if (empty($roleId)) {
            return false;
        }

        // 查询用户是否具有管理员角色
        $adminRelation = $this->userRoleRelationRepository->one([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        return empty($adminRelation) ? false : true;
    }
}
