<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_test_results', function (Blueprint $table) {
            $table->foreignId('interface_id')->after('test_case_id')->constrained('api_interfaces')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('api_test_results', function (Blueprint $table) {
            $table->dropForeign(['interface_id']);
            $table->dropColumn('interface_id');
        });
    }
};
