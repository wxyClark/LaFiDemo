<?php

namespace App\Modules\Dev\Models\User;

use Illuminate\Database\Eloquent\Model;

class RoleUserRelation extends Model
{
    protected $table = 'role_user_relations';

    protected $guarded = ['id'];

    public $timestamps = true;

    /**
     * 权限对应的角色
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author wxyClark
     * @create 2025/11/29 11:23
     *
     * @version 1.0
     */
    public function role()
    {
        return $this->hasOne(Role::class);
    }

    /**
     * 权限对应的用户
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author wxyClark
     * @create 2025/11/29 11:23
     *
     * @version 1.0
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
