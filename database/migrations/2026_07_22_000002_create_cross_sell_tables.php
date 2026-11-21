<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_sell_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('email', 191)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('plan', 30)->default('free');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('plan_starts_at')->nullable();
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('cross_sell_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('name', 100);
            $table->string('email', 191);
            $table->string('phone', 30)->nullable();
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'email']);
        });

        Schema::create('cross_sell_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 50);
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
        });

        Schema::create('cross_sell_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 100);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cross_sell_user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        Schema::create('cross_sell_warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 100);
            $table->string('code', 60)->nullable();
            $table->string('address')->nullable();
            $table->string('country', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cross_sell_platform_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('platform', 50);
            $table->string('shop_name', 100)->nullable();
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->boolean('sync_enabled')->default(false);
            $table->integer('sync_interval')->default(3600);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->string('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('cross_sell_logistics_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name', 100);
            $table->string('carrier', 100)->nullable();
            $table->string('code', 60)->nullable();
            $table->json('supported_countries')->nullable();
            $table->json('supported_weight')->nullable();
            $table->string('price_formula', 100)->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('price_per_kg', 10, 2)->default(0);
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cross_sell_product_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 100);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cross_sell_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('main_image')->nullable();
            $table->json('images')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_id', 60)->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cross_sell_skus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('sku_code', 64);
            $table->string('barcode', 64)->nullable();
            $table->string('name', 100)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('price_currency', 3)->default('USD');
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('cost_currency', 3)->nullable();
            $table->decimal('cost_usd', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('available_stock')->default(0);
            $table->json('attributes')->nullable();
            $table->string('status', 30)->default('active');
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku_code']);
        });

        Schema::create('cross_sell_product_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('language', 5);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'language']);
        });

        Schema::create('cross_sell_sku_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sku_id')->index();
            $table->string('language', 5);
            $table->string('name', 100);
            $table->timestamps();

            $table->index(['sku_id', 'language']);
        });

        Schema::create('cross_sell_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('sku_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->timestamps();

            $table->unique(['sku_id', 'warehouse_id']);
        });

        Schema::create('cross_sell_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('order_no', 64);
            $table->string('platform_order_id', 100)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('buyer_name', 100)->nullable();
            $table->string('buyer_email', 191)->nullable();
            $table->string('buyer_phone', 30)->nullable();
            $table->string('shipping_name', 100)->nullable();
            $table->string('shipping_phone', 30)->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('shipping_country', 50)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('total_usd', 10, 2)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_status', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('note')->nullable();
            $table->string('customer_note')->nullable();
            $table->json('platform_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_no');
        });

        Schema::create('cross_sell_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('sku_id')->nullable()->index();
            $table->string('product_title', 255)->nullable();
            $table->string('sku_code', 64)->nullable();
            $table->json('attributes')->nullable();
            $table->string('image')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
        });

        Schema::create('cross_sell_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('refund_no', 64);
            $table->string('reason')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount_usd', 10, 2)->nullable();
            $table->string('refund_method', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->json('attachments')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('cross_sell_refund_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id')->index();
            $table->unsignedBigInteger('order_item_id')->index();
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cross_sell_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('logistics_channel_id')->nullable();
            $table->string('shipment_no', 64);
            $table->string('tracking_no', 100)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('carrier', 100)->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('weight', 10, 3)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('tracking_data')->nullable();
            $table->timestamp('last_tracking_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cross_sell_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id')->index();
            $table->unsignedBigInteger('order_item_id')->index();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_sell_shipment_items');
        Schema::dropIfExists('cross_sell_shipments');
        Schema::dropIfExists('cross_sell_refund_items');
        Schema::dropIfExists('cross_sell_refunds');
        Schema::dropIfExists('cross_sell_order_items');
        Schema::dropIfExists('cross_sell_orders');
        Schema::dropIfExists('cross_sell_inventories');
        Schema::dropIfExists('cross_sell_sku_translations');
        Schema::dropIfExists('cross_sell_product_translations');
        Schema::dropIfExists('cross_sell_skus');
        Schema::dropIfExists('cross_sell_products');
        Schema::dropIfExists('cross_sell_product_categories');
        Schema::dropIfExists('cross_sell_logistics_channels');
        Schema::dropIfExists('cross_sell_platform_configs');
        Schema::dropIfExists('cross_sell_warehouses');
        Schema::dropIfExists('cross_sell_user_roles');
        Schema::dropIfExists('cross_sell_departments');
        Schema::dropIfExists('cross_sell_roles');
        Schema::dropIfExists('cross_sell_users');
        Schema::dropIfExists('cross_sell_tenants');
    }
};
