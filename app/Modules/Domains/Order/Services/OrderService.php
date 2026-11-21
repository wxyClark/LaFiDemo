<?php

declare(strict_types=1);

namespace App\Modules\Domains\Order\Services;

use App\Modules\Domains\Cart\Models\Cart;
use App\Modules\Domains\Cart\Services\CartService;
use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Catalog\Models\Sku;
use App\Modules\Domains\Order\Enums\OrderStatus;
use App\Modules\Domains\Order\Models\Order;
use App\Modules\Domains\Order\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function checkout(Cart $cart): Order
    {
        return DB::transaction(function () use ($cart) {
            $items = $this->cartService->selectedList($cart);

            if ($items->isEmpty()) {
                throw new \RuntimeException('购物车没有选中的商品');
            }

            $freight = $this->computeFreight($items);
            $goodsAmount = 0.0;

            $order = new Order;
            $order->order_no = $this->generateOrderNo();
            $order->user_id = $cart->user_id;
            $order->status = OrderStatus::PENDING->value;
            $order->freight = $freight;
            $order->save();

            foreach ($items as $item) {
                $sku = Sku::lockForUpdate()->findOrFail($item->sku_id);

                if ($sku->stock < $item->qty) {
                    throw new \RuntimeException("SKU #{$sku->id} 库存不足");
                }

                $sku->decrementStock($item->qty);

                $subtotal = (float) $sku->price * $item->qty;
                $goodsAmount += $subtotal;

                $row = new OrderItem;
                $row->order_id = $order->id;
                $row->sku_id = $sku->id;
                $row->product_id = $sku->product_id;
                $row->qty = $item->qty;
                $row->unit_price = $sku->price;
                $row->subtotal = $subtotal;
                $row->save();
            }

            $order->total_amount = $goodsAmount + $freight;
            $order->save();

            $this->cartService->clearSelected($cart);

            return $order;
        });
    }

    private function computeFreight($items): float
    {
        $hasEntity = $items->contains(fn ($item) => $item->sku->product->type === ProductType::ENTITY->value);

        return $hasEntity ? 10.0 : 0.0;
    }

    private function generateOrderNo(): string
    {
        return 'NO'.date('YmdHis').Str::padLeft((string) mt_rand(0, 9999), 4, '0');
    }
}
