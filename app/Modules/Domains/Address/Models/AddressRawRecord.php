<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 行政区划接口原始记录（每次接口请求一条，原始数据逐条落库）
 */
class AddressRawRecord extends Model
{
    protected $table = 'address_raw_records';

    protected $guarded = [];

    protected $casts = [
        'http_status' => 'integer',
        'max_level' => 'integer',
        'year' => 'integer',
        'response_size' => 'integer',
        'node_count' => 'integer',
        'fetched_at' => 'datetime',
    ];
}
