<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Models;

use App\Modules\Domains\Base\ApiTesting\Enums\AuthType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiEnvironment extends Model
{
    protected $table = 'api_environments';

    protected $fillable = [
        'name',
        'base_url',
        'auth_type',
        'auth_config',
        'headers',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'auth_type' => AuthType::class,
        'auth_config' => 'array',
        'headers' => 'array',
        'is_default' => 'boolean',
    ];

    public function testCases(): HasMany
    {
        return $this->hasMany(ApiTestCase::class, 'environment_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(ApiTestResult::class);
    }

    public function setAsDefault(): void
    {
        static::query()->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
