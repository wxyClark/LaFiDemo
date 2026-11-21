<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Models;

use App\Modules\Domains\Base\ApiTesting\Enums\TestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiTestResult extends Model
{
    protected $table = 'api_test_results';

    protected $fillable = [
        'test_case_id',
        'interface_id',
        'environment_id',
        'status',
        'request_url',
        'request_method',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'response_time',
        'assertion_results',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'status' => TestStatus::class,
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'assertion_results' => 'array',
        'executed_at' => 'datetime',
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(ApiTestCase::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApiEnvironment::class);
    }

    public function interface(): BelongsTo
    {
        return $this->belongsTo(ApiInterface::class);
    }
}
