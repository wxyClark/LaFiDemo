<?php

namespace App\Modules\Dev\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Query extends Model
{
    protected $table = 'query';

    protected $guarded = ['id'];

    public $timestamps = true;

    /**
     * 用户拥有的角色权限
     *
     * @return HasMany
     *
     * @author wxyClark
     *
     * @create 2025/11/29 11:23
     *
     * @version 1.0
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
