<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Models;

use App\Modules\Domains\Base\ApiTesting\Enums\HttpMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiInterface extends Model
{
    use HasFactory;

    protected $table = 'api_interfaces';

    protected $fillable = [
        'function_id',
        'name',
        'description',
        'method',
        'path',
        'headers',
        'body_type',
        'body_schema',
        'auth_required',
        'tags',
        'sort_order',
    ];

    protected $casts = [
        'method' => HttpMethod::class,
        'headers' => 'array',
        'body_schema' => 'array',
        'tags' => 'array',
        'auth_required' => 'boolean',
    ];

    public function function(): BelongsTo
    {
        return $this->belongsTo(ApiFunction::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(ApiTestCase::class, 'interface_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(ApiTestResult::class, 'interface_id');
    }

    public function getLatestTestResult()
    {
        return $this->testResults()
            ->orderByDesc('executed_at')
            ->first();
    }
}
