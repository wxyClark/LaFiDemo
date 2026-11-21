<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin audit log table backing App\Modules\Domains\Base\Logging\Models\AdminAuditLog.
 * The model existed in the source baseline but no migration ever created this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name', 255)->nullable();
            $table->string('admin_email', 255)->nullable();
            $table->string('action', 50)->index();
            $table->string('subject_type', 255)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
