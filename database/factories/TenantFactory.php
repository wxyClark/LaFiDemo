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
