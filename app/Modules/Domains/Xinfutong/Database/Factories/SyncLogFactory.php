<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Database\Factories;

use App\Modules\Domains\Xinfutong\Enums\SyncStatus;
use App\Modules\Domains\Xinfutong\Models\SyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 day', 'now');

        return [
            'employee_id' => null,
            'sync_type' => 'full',
            'status' => SyncStatus::SUCCESS,
            'records_fetched' => $this->faker->numberBetween(1, 500),
            'records_created' => $this->faker->numberBetween(0, 100),
            'records_updated' => $this->faker->numberBetween(0, 50),
            'error_message' => null,
            'started_at' => $startedAt,
            'completed_at' => (new \DateTimeImmutable('@'.$startedAt->getTimestamp()))->modify('+'.$this->faker->numberBetween(1, 30).' seconds'),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SyncStatus::FAILED,
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
