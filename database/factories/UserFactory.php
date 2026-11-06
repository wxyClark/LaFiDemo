<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * 平台运营管理员（双 Panel 基线；role=platform，无租户归属）。
     */
    public function platform(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Platform,
            'tenant_id' => null,
        ]);
    }

    /**
     * 租户商家用户：绑定指定租户（role=tenant + tenant_id）。
     */
    public function tenantUser(?Tenant $tenant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Tenant,
            'tenant_id' => $tenant?->id,
        ]);
    }
}
