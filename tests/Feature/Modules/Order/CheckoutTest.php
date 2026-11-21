<?php

use App\Models\User;
use App\Modules\Domains\Cart\Models\Cart;
use App\Modules\Domains\Cart\Services\CartService;
use App\Modules\Domains\Catalog\Database\Factories\ProductFactory;
use App\Modules\Domains\Catalog\Database\Factories\SkuFactory;
use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Order\Enums\OrderStatus;
use App\Modules\Domains\Order\Models\Order;
use App\Modules\Domains\Order\Models\OrderItem;
use App\Modules\Domains\Order\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** 交易主链路（Cart -> Order -> 库存扣减）：此前完全没有测试覆盖。 */
test('checkout converts a selected cart into an order and decrements stock', function (): void {
    $user = User::factory()->create();

    $product = ProductFactory::new()->create(['type' => ProductType::ENTITY->value]);
    $sku = SkuFactory::new()->create([
        'product_id' => $product->id,
        'price' => '100.00',
        'stock' => 10,
    ]);

    $cart = app(CartService::class)->forUser($user->id);
    app(CartService::class)->addItem($cart, $sku->id, 3);

    $order = app(OrderService::class)->checkout($cart);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBe(OrderStatus::PENDING->value)
        ->and($order->freight)->toBe('10.00')
        ->and($order->total_amount)->toBe('310.00')
        ->and($order->order_no)->toMatch('/^NO[0-9]{18}$/');

    expect(OrderItem::count())->toBe(1)
        ->and(OrderItem::first()->qty)->toBe(3)
        ->and(OrderItem::first()->unit_price)->toBe('100.00')
        ->and(OrderItem::first()->subtotal)->toBe('300.00');

    expect($sku->fresh()->stock)->toBe(7)
        ->and(Cart::firstOrFail()->selectedItems()->count())->toBe(0);
});

test('an empty cart cannot be checked out', function (): void {
    $user = User::factory()->create();
    $cart = app(CartService::class)->forUser($user->id);

    expect(fn () => app(OrderService::class)->checkout($cart))
        ->toThrow(RuntimeException::class)
        ->and(Order::count())->toBe(0);
});

test('insufficient stock rolls back the whole order', function (): void {
    $user = User::factory()->create();

    $product = ProductFactory::new()->create(['type' => ProductType::ENTITY->value]);
    $sku = SkuFactory::new()->create([
        'product_id' => $product->id,
        'price' => '10.00',
        'stock' => 1,
    ]);

    $cart = app(CartService::class)->forUser($user->id);

    // 直接落库，绕过 addItem 的库存前置校验：本用例验证的是 checkout 阶段的回滚
    $cart->items()->create(['sku_id' => $sku->id, 'qty' => 2, 'selected' => true]);

    expect(fn () => app(OrderService::class)->checkout($cart))
        ->toThrow(RuntimeException::class)
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and($sku->fresh()->stock)->toBe(1);
});
