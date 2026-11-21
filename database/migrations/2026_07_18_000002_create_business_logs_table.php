<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 36)->nullable()->comment('关联的请求 ID');
            $table->string('level', 20)->comment('日志级别 (debug/info/warning/error/critical)');
            $table->string('channel', 50)->default('default')->comment('日志通道');
            $table->text('message')->comment('日志消息');
            $table->json('context')->nullable()->comment('上下文数据');
            $table->json('extra')->nullable()->comment('额外数据');
            $table->string('file', 500)->nullable()->comment('触发文件');
            $table->unsignedInteger('line')->nullable()->comment('触发行号');
            $table->text('trace')->nullable()->comment('调用堆栈');
            $table->timestamps();

            $table->index('request_id');
            $table->index('level');
            $table->index('channel');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_logs');
    }
};
