<?php

declare(strict_types=1);

namespace App\Modules\Domains\ApiTesting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApiTestCase extends Model
{
    protected $table = 'api_test_cases';

    protected $fillable = [
        'interface_id',
        'environment_id',
        'name',
        'description',
        'headers',
        'query_params',
        'body',
        'expected_status',
        'expected_structure',
        'expected_data',
        'expected_response_time',
        'sort_order',
    ];

    protected $casts = [
        'headers' => 'array',
        'query_params' => 'array',
        'body' => 'array',
        'expected_structure' => 'array',
        'expected_data' => 'array',
    ];

    public function interface(): BelongsTo
    {
        return $this->belongsTo(ApiInterface::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApiEnvironment::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ApiTestResult::class, 'test_case_id');
    }

    public function getLatestResult()
    {
        return $this->results()
            ->orderByDesc('executed_at')
            ->first();
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(ApiTestResult::class, 'test_case_id')
            ->ofMany('executed_at', 'max');
    }
}
