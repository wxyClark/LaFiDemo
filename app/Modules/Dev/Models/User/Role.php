<?php

namespace App\Modules\Dev\Models\User;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $guarded = ['id'];

    public $timestamps = true;

    /**
     * 用户拥有的角色权限
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author wxyClark
     * @create 2025/11/29 11:23
     *
     * @version 1.0
     */
    public function roleUserRelation()
    {
        return $this->hasMany(RoleUserRelation::class);
    }
}
