<?php

namespace App\Modules\Dev\Repositories\User;

use App\Modules\Dev\Models\User\user;
use App\Modules\Dev\Repositories\BaseRepository;
use App\Modules\Dev\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Created by PhpStorm
 * Author: wxyClark
 * Date: 2025/11/29
 * Time: 11:29
 * Email: C18666211369@outlook.com
 */
class UserRepository extends BaseRepository implements BaseRepositoryInterface
{
    protected $inTermFields = [
        'id', 'name', 'status', 'email'
    ];
    protected $timeRangeFields = [
        'created_at', 'updated_at'
    ];

    protected function model(): string
    {
        return User::class;
    }

}
