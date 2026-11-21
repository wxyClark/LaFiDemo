<?php

declare(strict_types=1);

namespace App\Modules\Domains\Notification\Listeners;

use App\Modules\Domains\Catalog\Models\Product;
use App\Modules\Domains\Notification\Events\LowStockDetected;
use App\Modules\Domains\Notification\Models\AdminNotification;
use App\Modules\Models\Admin;

class SendLowStockNotification
{
    public function handle(LowStockDetected $event): void
    {
        $admins = Admin::pluck('id');
        /** @var Product|null $product */
        $product = $event->sku->product;

        foreach ($admins as $adminId) {
            AdminNotification::notifyLowStock(
                adminId: $adminId,
                productName: optional($product)->title ?? '未知商品',
                skuCode: $event->sku->sku_code,
                currentStock: $event->currentStock,
                threshold: $event->threshold,
            );
        }
    }
}
