<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('type', 50)->comment('类型: order_status_changed/low_stock/system');
            $table->string('title', 200)->comment('标题');
            $table->text('message')->nullable()->comment('内容');
            $table->string('subject_type', 100)->nullable()->comment('关联对象类型');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('关联对象ID');
            $table->json('data')->nullable()->comment('附加数据');
            $table->boolean('is_read')->default(false)->comment('是否已读');
            $table->timestamp('read_at')->nullable()->comment('阅读时间');
            $table->timestamps();

            $table->index('type');
            $table->index(['admin_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
