<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fulfillment_id');
            $table->string('code')->unique();
            $table->string('password');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('fulfillment_id')->references('id')->on('fulfillments')->onDelete('cascade');
            $table->index('fulfillment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_codes');
    }
};
