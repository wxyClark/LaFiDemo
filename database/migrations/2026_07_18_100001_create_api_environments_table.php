<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_environments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('base_url', 500);
            $table->enum('auth_type', ['none', 'jwt', 'session', 'apikey'])->default('none');
            $table->json('auth_config')->nullable();
            $table->json('headers')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_environments');
    }
};
