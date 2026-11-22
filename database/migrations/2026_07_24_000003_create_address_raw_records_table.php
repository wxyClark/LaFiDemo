<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_raw_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id')->nullable()->comment('关联 address_import_logs.id（本次导入批次）');
            $table->string('source', 30)->default('mca')->comment('数据源');
            $table->string('request_code', 20)->default('')->comment('请求的行政区划码（省级入口为空串）');
            $table->unsignedTinyInteger('max_level')->default(3)->comment('请求的 maxLevel');
            $table->unsignedSmallInteger('year')->nullable()->comment('请求的 year');
            $table->unsignedSmallInteger('http_status')->nullable()->comment('HTTP 状态码');
            $table->longText('response_raw')->nullable()->comment('接口原始响应（逐请求落库）');
            $table->unsignedInteger('response_size')->nullable()->comment('原始响应字节数');
            $table->unsignedInteger('node_count')->nullable()->comment('解析出的行政区划节点数');
            $table->string('status', 20)->default('success')->comment('success/failed');
            $table->text('error_message')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index('log_id');
            $table->index(['source', 'request_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_raw_records');
    }
};
