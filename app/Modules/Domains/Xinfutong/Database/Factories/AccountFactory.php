<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Database\Factories;

use App\Modules\Domains\Xinfutong\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->unique()->company(),
            'base_url' => 'https://xft.cmbchina.com/open',
            'app_id' => $this->faker->uuid(),
            'authority_secret' => $this->faker->sha256(),
            'sign_private_key' => "-----BEGIN PRIVATE KEY-----\n".$this->faker->sha256()."\n-----END PRIVATE KEY-----",
            'verify_public_key' => "-----BEGIN PUBLIC KEY-----\n".$this->faker->sha256()."\n-----END PUBLIC KEY-----",
            'timeout' => 30,
            'is_default' => false,
            'is_active' => true,
            'remark' => $this->faker->optional()->sentence(),
        ];
    }
}
