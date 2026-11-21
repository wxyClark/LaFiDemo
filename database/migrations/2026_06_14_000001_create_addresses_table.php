<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('name', 100)->comment('地区名称');
            $table->string('code', 60)->unique()->comment('行政区划代码');
            $table->string('level', 20)->comment('层级: country/province/city/district');
            $table->integer('level_num')->default(1)->comment('层级深度: 1=省, 2=市, 3=区县');
            $table->string('pinyin', 100)->nullable()->comment('拼音');
            $table->json('merge_path')->nullable()->comment('合并路径: ["国家","省","市"]');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('code');
            $table->index('level');
            $table->index('level_num');
            $table->index('parent_id');
            $table->index(['level', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
