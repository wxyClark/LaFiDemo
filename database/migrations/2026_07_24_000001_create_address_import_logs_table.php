<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->default('mca')->comment('数据源: mca=民政部/国家地名信息库');
            $table->string('endpoint', 500)->nullable()->comment('请求接口地址');
            $table->json('params')->nullable()->comment('请求参数');
            $table->unsignedInteger('http_status')->nullable()->comment('HTTP 状态码');
            $table->longText('response_raw')->nullable()->comment('接口原始响应（原始数据落库）');
            $table->unsignedInteger('response_size')->nullable()->comment('原始响应字节数');
            $table->unsignedInteger('node_count')->nullable()->comment('解析出的行政区划节点数');
            $table->unsignedInteger('imported_count')->nullable()->comment('写入 addresses 的条数');
            $table->string('status', 20)->default('pending')->comment('pending/success/failed');
            $table->text('error_message')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'status']);
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_import_logs');
    }
};
