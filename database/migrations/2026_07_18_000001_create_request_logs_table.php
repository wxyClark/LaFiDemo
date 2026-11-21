<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 36)->unique()->comment('请求唯一标识');
            $table->string('method', 10)->comment('HTTP 方法');
            $table->string('path', 500)->comment('请求路径');
            $table->string('controller', 200)->nullable()->comment('控制器');
            $table->string('action', 100)->nullable()->comment('方法');
            $table->string('user_type', 100)->nullable()->comment('用户类型');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户 ID');
            $table->string('user_name', 100)->nullable()->comment('用户名');
            $table->string('ip_address', 45)->comment('IP 地址');
            $table->string('client_type', 50)->nullable()->comment('客户端类型 (web/api/mobile)');
            $table->text('user_agent')->nullable()->comment('User Agent');
            $table->json('request_headers')->nullable()->comment('请求 Headers');
            $table->json('request_body')->nullable()->comment('请求 Body');
            $table->json('query_params')->nullable()->comment('Query 参数');
            $table->smallInteger('response_status')->comment('响应状态码');
            $table->json('response_body')->nullable()->comment('响应 Body');
            $table->unsignedInteger('response_time')->comment('响应时间 (ms)');
            $table->unsignedInteger('memory_usage')->nullable()->comment('内存使用 (bytes)');
            $table->string('exception_class', 200)->nullable()->comment('异常类名');
            $table->text('exception_message')->nullable()->comment('异常消息');
            $table->text('exception_trace')->nullable()->comment('异常堆栈');
            $table->timestamps();

            $table->index('created_at');
            $table->index(['method', 'path']);
            $table->index(['user_type', 'user_id']);
            $table->index('response_status');
            $table->index('exception_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
