<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xinfutong_employees', function (Blueprint $table) {
            $table->id();
            $table->string('xinfutong_id', 64)->unique()->comment('薪福通系统员工 ID');
            $table->string('employee_no', 64)->index()->comment('工号');
            $table->string('name', 100)->comment('姓名');
            $table->string('gender', 10)->nullable()->comment('性别');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('department', 100)->nullable()->comment('部门');
            $table->string('position', 100)->nullable()->comment('职位');
            $table->date('hire_date')->nullable()->comment('入职日期');
            $table->string('status', 20)->default('active')->index()->comment('员工状态');
            $table->json('raw_data')->nullable()->comment('原始 API 返回数据');
            $table->datetime('last_synced_at')->nullable()->comment('最后同步时间');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xinfutong_employees');
    }
};
