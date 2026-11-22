<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('address_import_logs', function (Blueprint $table) {
            $table->unsignedInteger('drill_count')->nullable()->after('imported_count')->comment('下钻查询次数（乡镇级）');
            $table->unsignedInteger('throttle_ms')->nullable()->after('drill_count')->comment('实际使用的请求间隔(毫秒)');
            $table->longText('drill_raw')->nullable()->after('throttle_ms')->comment('逐下钻的原始响应(JSON数组: code/maxLevel/http_status/raw/node_count/error)');
        });
    }

    public function down(): void
    {
        Schema::table('address_import_logs', function (Blueprint $table) {
            $table->dropColumn(['drill_count', 'throttle_ms', 'drill_raw']);
        });
    }
};
