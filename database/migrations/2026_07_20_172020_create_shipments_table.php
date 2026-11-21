<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fulfillment_id');
            $table->string('carrier')->nullable();
            $table->string('tracking_no')->nullable();
            $table->timestamps();

            $table->foreign('fulfillment_id')->references('id')->on('fulfillments')->onDelete('cascade');
            $table->index('fulfillment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
