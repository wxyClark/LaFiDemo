<?php

declare(strict_types=1);

namespace App\Modules\Domains\Fulfillment\Services;

use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Fulfillment\Enums\FulfillmentType;
use App\Modules\Domains\Fulfillment\Models\Fulfillment;
use App\Modules\Domains\Fulfillment\Models\Shipment;
use App\Modules\Domains\Fulfillment\Models\VirtualCode;
use App\Modules\Domains\Order\Models\Order;
use Illuminate\Support\Str;

class FulfillmentService
{
    public function createFromOrder(Order $order): Fulfillment
    {
        if ($order->statusEnum()->value !== 'paid') {
            throw new \RuntimeException('仅已支付订单可履约');
        }

        $isVirtual = $order->load('items.sku.product')->items->contains(
            fn ($item) => $item->sku && $item->sku->product
                && $item->sku->product->type === ProductType::VIRTUAL->value
        );

        $type = $isVirtual ? FulfillmentType::VIRTUAL : FulfillmentType::ENTITY;

        $fulfillment = new Fulfillment;
        $fulfillment->order_id = $order->id;
        $fulfillment->type = $type->value;
        $fulfillment->status = 'pending';
        $fulfillment->save();

        if ($type === FulfillmentType::VIRTUAL) {
            $this->deliverVirtual($fulfillment);
        }

        return $fulfillment;
    }

    public function ship(Fulfillment $fulfillment, string $carrier, string $trackingNo): Shipment
    {
        if ($fulfillment->isVirtual()) {
            throw new \RuntimeException('虚拟履约不需要物流单');
        }

        $shipment = new Shipment;
        $shipment->fulfillment_id = $fulfillment->id;
        $shipment->carrier = $carrier;
        $shipment->tracking_no = $trackingNo;
        $shipment->save();

        $fulfillment->markDelivered();

        return $shipment;
    }

    /**
     * 重发虚拟码（含明文密码，仅限虚拟履约）。
     *
     * @return array<int, array{code: string, password: string}>
     */
    public function resendVirtualCodes(Fulfillment $fulfillment): array
    {
        if (! $fulfillment->isVirtual()) {
            throw new \RuntimeException('仅虚拟履约可重发');
        }

        return $fulfillment->virtualCodes->map(
            fn (VirtualCode $code) => [
                'code' => $code->code,
                'password' => $code->plainPassword(),
            ]
        )->all();
    }

    private function deliverVirtual(Fulfillment $fulfillment): void
    {
        $code = new VirtualCode;
        $code->fulfillment_id = $fulfillment->id;
        $code->code = $this->generateUniqueCode();
        $code->password = Str::random(12);
        $code->delivered_at = now();
        $code->save();

        $fulfillment->markDelivered();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'VC'.Str::upper(Str::random(16));
        } while (VirtualCode::where('code', $code)->exists());

        return $code;
    }
}
