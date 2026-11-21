<?php

declare(strict_types=1);

namespace App\Modules\Domains\Notification\Listeners;

use App\Modules\Domains\Notification\Events\OrderStatusChanged;
use App\Modules\Domains\Notification\Models\AdminNotification;
use App\Modules\Models\Admin;

class SendOrderStatusNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        $admins = Admin::pluck('id');

        foreach ($admins as $adminId) {
            AdminNotification::notifyOrderStatusChanged(
                adminId: $adminId,
                orderNo: $event->order->order_no,
                oldStatus: $event->oldStatus,
                newStatus: $event->newStatus,
            );
        }
    }
}
