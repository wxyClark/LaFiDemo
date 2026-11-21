<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xinfutong_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('xinfutong_accounts')->nullOnDelete()->comment('关联账号');
            $table->foreignId('employee_id')->nullable()->constrained('xinfutong_employees')->nullOnDelete()->comment('关联员工（全量同步时为 null）');
            $table->string('sync_type', 20)->default('full')->comment('同步类型: full/incremental');
            $table->string('status', 20)->default('pending')->comment('同步状态: pending/success/failed');
            $table->unsignedInteger('records_fetched')->default(0)->comment('拉取记录数');
            $table->unsignedInteger('records_created')->default(0)->comment('新增记录数');
            $table->unsignedInteger('records_updated')->default(0)->comment('更新记录数');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->datetime('started_at')->nullable()->comment('开始时间');
            $table->datetime('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xinfutong_sync_logs');
    }
};
