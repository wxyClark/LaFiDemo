<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xinfutong_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 100)->unique()->comment('企业名称（唯一）');
            $table->string('base_url', 500)->default('https://xft.cmbchina.com/open')->comment('API 基础地址');
            $table->string('app_id', 100)->comment('应用 ID');
            $table->string('authority_secret', 200)->comment('权限密钥');
            $table->text('sign_private_key')->comment('SM2 签名私钥');
            $table->text('verify_public_key')->nullable()->comment('SM2 验签公钥');
            $table->integer('timeout')->default(30)->comment('请求超时时间（秒）');
            $table->boolean('is_default')->default(false)->comment('是否默认账号');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_default');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xinfutong_accounts');
    }
};
