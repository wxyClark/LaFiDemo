<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('sku_id');
            $table->unsignedInteger('qty')->default(1);
            $table->boolean('selected')->default(true);
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('sku_id')->references('id')->on('skus')->onDelete('cascade');
            $table->unique(['cart_id', 'sku_id']);
            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
