<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xinfutong_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('xinfutong_accounts')->nullOnDelete()->comment('关联账号');
            $table->string('approval_no', 64)->unique()->comment('审批单号');
            $table->string('approval_type', 50)->comment('审批类型: leave/expense/hire/resign/other');
            $table->string('title', 200)->comment('审批标题');
            $table->text('description')->nullable()->comment('审批说明');
            $table->json('form_data')->comment('表单数据');
            $table->string('status', 20)->default('draft')->comment('状态: draft/pending/approved/rejected/withdrawn');
            $table->string('applicant_name', 100)->comment('申请人姓名');
            $table->string('applicant_id', 64)->nullable()->comment('申请人员工 ID');
            $table->string('xinfutong_approval_id', 64)->nullable()->comment('薪福通审批单 ID');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->json('raw_response')->nullable()->comment('原始 API 响应');
            $table->datetime('submitted_at')->nullable()->comment('提交时间');
            $table->datetime('approved_at')->nullable()->comment('审批完成时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('approval_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xinfutong_approval_requests');
    }
};
