<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interface_id')->constrained('api_interfaces')->cascadeOnDelete();
            $table->foreignId('environment_id')->constrained('api_environments')->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('body')->nullable();
            $table->smallInteger('expected_status')->default(200);
            $table->json('expected_structure')->nullable();
            $table->json('expected_data')->nullable();
            $table->integer('expected_response_time')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_test_cases');
    }
};
