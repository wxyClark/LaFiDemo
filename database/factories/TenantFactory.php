<?php

namespace Database\Factories;

use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * 模块模型不在 App\Models 下（ADR-0001 a/g 条），自动工厂解析
     * 无法命中 Database\Factories\TenantFactory，显式指定所属模型。
     */
    public function modelName(): string
    {
        return Tenant::class;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'status' => TenantStatus::Active,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['status' => TenantStatus::Disabled]);
    }
}
