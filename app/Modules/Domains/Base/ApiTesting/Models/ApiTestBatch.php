<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiTestBatch extends Model
{
    use HasFactory;

    protected $table = 'api_test_batches';

    protected $fillable = [
        'name',
        'test_case_ids',
    ];

    protected $casts = [
        'test_case_ids' => 'array',
    ];
}
