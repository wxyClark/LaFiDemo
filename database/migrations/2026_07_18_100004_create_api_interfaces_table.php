<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('function_id')->constrained('api_functions')->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
            $table->string('path', 500);
            $table->json('headers')->nullable();
            $table->enum('body_type', ['json', 'form', 'raw', 'none'])->default('json');
            $table->json('body_schema')->nullable();
            $table->boolean('auth_required')->default(true);
            $table->json('tags')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_interfaces');
    }
};
