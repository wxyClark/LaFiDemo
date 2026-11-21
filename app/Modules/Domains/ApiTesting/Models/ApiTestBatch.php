<?php

declare(strict_types=1);

namespace App\Modules\Domains\ApiTesting\Models;

use Illuminate\Database\Eloquent\Model;

class ApiTestBatch extends Model
{
    protected $table = 'api_test_batches';

    protected $fillable = [
        'name',
        'test_case_ids',
    ];

    protected $casts = [
        'test_case_ids' => 'array',
    ];
}
