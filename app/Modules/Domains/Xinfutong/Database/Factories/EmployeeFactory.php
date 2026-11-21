<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Database\Factories;

use App\Modules\Domains\Xinfutong\Enums\EmployeeStatus;
use App\Modules\Domains\Xinfutong\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'xinfutong_id' => (string) $this->faker->unique()->randomNumber(6),
            'employee_no' => $this->faker->unique()->numerify('EMP#####'),
            'name' => $this->faker->name('zh_CN'),
            'gender' => $this->faker->randomElement(['男', '女']),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'department' => $this->faker->randomElement(['技术部', '产品部', '市场部', '人事部', '财务部']),
            'position' => $this->faker->randomElement(['工程师', '产品经理', '设计师', '运营', '经理']),
            'hire_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'status' => EmployeeStatus::ACTIVE,
            'raw_data' => null,
            'last_synced_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
