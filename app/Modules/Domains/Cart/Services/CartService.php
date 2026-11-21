<?php

declare(strict_types=1);

namespace App\Modules\Domains\Cart\Services;

use App\Modules\Domains\Cart\Models\Cart;
use App\Modules\Domains\Cart\Models\CartItem;
use App\Modules\Domains\Catalog\Models\Sku;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public function forUser(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    public function addItem(Cart $cart, int $skuId, int $qty = 1, bool $selected = true): CartItem
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('数量必须大于 0');
        }

        $sku = Sku::findOrFail($skuId);
        if ($sku->stock < $qty) {
            throw new \RuntimeException("SKU #{$skuId} 库存不足");
        }

        /** @var CartItem|null $item */
        $item = $cart->items()->where('sku_id', $skuId)->first();

        if ($item) {
            $item->qty += $qty;
            $item->selected = $selected;
            $item->save();

            return $item;
        }

        $item = new CartItem;
        $item->cart_id = $cart->id;
        $item->sku_id = $skuId;
        $item->qty = $qty;
        $item->selected = $selected;
        $item->save();

        return $item;
    }

    public function setSelected(CartItem $item, bool $selected): void
    {
        $item->selected = $selected;
        $item->save();
    }

    public function updateQty(CartItem $item, int $qty): void
    {
        if ($qty <= 0) {
            $item->delete();

            return;
        }

        $item->qty = $qty;
        $item->save();
    }

    public function selectedTotal(Cart $cart): float
    {
        return (float) $cart->selectedItems()
            ->with('sku')
            ->get()
            ->sum(fn (CartItem $item) => (float) ($item->sku->price ?? 0) * $item->qty);
    }

    public function clearSelected(Cart $cart): void
    {
        $cart->selectedItems()->delete();
    }

    public function selectedList(Cart $cart): Collection
    {
        return $cart->selectedItems()->with('sku.product')->get();
    }
}
