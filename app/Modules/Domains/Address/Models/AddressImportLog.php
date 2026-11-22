<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 行政区划接口拉取日志（记录接口原始数据落库）
 */
class AddressImportLog extends Model
{
    protected $table = 'address_import_logs';

    protected $guarded = [];

    protected $casts = [
        'params' => 'array',
        'http_status' => 'integer',
        'response_size' => 'integer',
        'node_count' => 'integer',
        'imported_count' => 'integer',
        'drill_count' => 'integer',
        'throttle_ms' => 'integer',
        'fetched_at' => 'datetime',
    ];
}
