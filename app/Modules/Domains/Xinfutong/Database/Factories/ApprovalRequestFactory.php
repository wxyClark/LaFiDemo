<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Database\Factories;

use App\Modules\Domains\Xinfutong\Enums\ApprovalStatus;
use App\Modules\Domains\Xinfutong\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    public function definition(): array
    {
        $types = ['leave', 'expense', 'hire', 'resign', 'other'];
        $type = $this->faker->randomElement($types);

        return [
            'approval_no' => 'APR'.date('Ymd').$this->faker->unique()->numerify('#####'),
            'approval_type' => $type,
            'title' => match ($type) {
                'leave' => $this->faker->randomElement(['年假申请', '事假申请', '病假申请', '调休申请']),
                'expense' => $this->faker->randomElement(['差旅报销', '办公用品报销', '招待费报销']),
                'hire' => '入职审批 - '.$this->faker->name('zh_CN'),
                'resign' => '离职审批 - '.$this->faker->name('zh_CN'),
                default => $type.'审批',
            },
            'description' => $this->faker->sentence(),
            'form_data' => [
                'applicant' => $this->faker->name('zh_CN'),
                'department' => $this->faker->randomElement(['技术部', '产品部', '市场部', '人事部']),
                'start_date' => $this->faker->date(),
                'end_date' => $this->faker->date(),
                'reason' => $this->faker->sentence(),
            ],
            'status' => $this->faker->randomElement(ApprovalStatus::cases()),
            'applicant_name' => $this->faker->name('zh_CN'),
            'applicant_id' => $this->faker->numerify('EMP#####'),
            'xinfutong_approval_id' => null,
            'error_message' => null,
            'raw_response' => null,
            'submitted_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'approved_at' => null,
        ];
    }
}
