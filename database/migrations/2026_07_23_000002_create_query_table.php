<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQL 审计表（App\Modules\Dev\Models\Query）。
 *
 * Dev 模块的查询记录此前只有模型、没有建表迁移，createQuery() 一旦执行即报错。
 * 经 BaseRepository::create() 走 Query Builder insert()，不会自动补时间戳，
 * 故 created_at / updated_at 可空。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('query', function (Blueprint $table) {
            $table->id();
            $table->string('query_code', 36)->unique();
            $table->text('sql');
            $table->float('exec_time')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query');
    }
};
