<?php

declare(strict_types=1);

namespace App\Modules\Domains\AfterSale\Services;

use App\Modules\Domains\AfterSale\Enums\AfterSaleStatus;
use App\Modules\Domains\AfterSale\Enums\AfterSaleType;
use App\Modules\Domains\AfterSale\Models\AfterSale;
use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Catalog\Models\Sku;
use App\Modules\Domains\Order\Models\Order;
use App\Modules\Domains\Payment\Services\PaymentService;
use Illuminate\Support\Facades\DB;

class AfterSaleService
{
    public function __construct(
        private readonly PaymentService $payment,
    ) {}

    public function request(Order $order, AfterSaleType $type, float $refundAmount): AfterSale
    {
        if ($order->statusEnum()->value !== 'paid' && $order->statusEnum()->value !== 'completed') {
            throw new \RuntimeException('仅已支付或已完成订单可申请售后');
        }

        if ($type === AfterSaleType::RETURN && $order->load('items.sku.product')->items->contains(
            fn ($item) => $item->sku && $item->sku->product
                && $item->sku->product->type === ProductType::VIRTUAL->value
        )) {
            throw new \RuntimeException('虚拟商品不支持退货，仅退款');
        }

        $afterSale = new AfterSale;
        $afterSale->order_id = $order->id;
        $afterSale->user_id = $order->user_id;
        $afterSale->type = $type->value;
        $afterSale->status = AfterSaleStatus::PENDING->value;
        $afterSale->refund_amount = $refundAmount;
        $afterSale->save();

        return $afterSale;
    }

    public function approve(AfterSale $afterSale): void
    {
        DB::transaction(function () use ($afterSale) {
            $order = Order::lockForUpdate()->findOrFail($afterSale->order_id);

            $this->payment->refund($afterSale->user_id, (float) $afterSale->refund_amount, $order->id);

            if ($afterSale->isReturn()) {
                foreach ($order->load('items.sku')->items as $item) {
                    if ($item->sku) {
                        Sku::where('id', $item->sku_id)->increment('stock', $item->qty);
                    }
                }
            }

            $afterSale->complete();
        });
    }
}
