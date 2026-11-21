<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('type')->comment('pay|recharge|refund');
            $table->decimal('amount', 10, 2);
            $table->string('gateway')->comment('balance|...');
            $table->string('status')->default('success')->comment('pending|success|failed');
            $table->timestamps();

            $table->index(['user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
