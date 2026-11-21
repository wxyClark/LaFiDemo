<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained('api_test_cases')->cascadeOnDelete();
            $table->foreignId('environment_id')->constrained('api_environments')->cascadeOnDelete();
            $table->enum('status', ['pass', 'fail', 'error', 'skip']);
            $table->string('request_url', 1000);
            $table->string('request_method', 10);
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->smallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('response_time')->nullable();
            $table->json('assertion_results')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_test_results');
    }
};
