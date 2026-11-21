<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_test_results', function (Blueprint $table) {
            $table->index(['status', 'executed_at'], 'api_test_results_status_executed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('api_test_results', function (Blueprint $table) {
            $table->dropIndex('api_test_results_status_executed_at_index');
        });
    }
};
