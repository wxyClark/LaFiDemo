<?php

declare(strict_types=1);

namespace App\Modules\Domains\Payment\Gateways;

use App\Modules\Domains\Order\Enums\OrderStatus;
use App\Modules\Domains\Order\Models\Order;
use App\Modules\Domains\Payment\Enums\PaymentGatewayType;
use App\Modules\Domains\Payment\Enums\PaymentType;
use App\Modules\Domains\Payment\Models\PaymentRecord;
use App\Modules\Domains\Payment\Models\Wallet;
use Illuminate\Support\Facades\DB;

class BalanceGateway implements PaymentGateway
{
    public function type(): string
    {
        return PaymentGatewayType::BALANCE->value;
    }

    public function supports(string $gateway): bool
    {
        return $gateway === PaymentGatewayType::BALANCE->value;
    }

    public function pay(Order $order): PaymentRecord
    {
        if ($order->statusEnum() !== OrderStatus::PENDING) {
            throw new \RuntimeException('订单状态不可支付');
        }

        return DB::transaction(function () use ($order) {
            $wallet = Wallet::lockForUpdate()->firstOrCreate(['user_id' => $order->user_id]);

            if ((float) $wallet->balance < (float) $order->total_amount) {
                throw new \RuntimeException('余额不足');
            }

            $wallet->balance = (float) $wallet->balance - (float) $order->total_amount;
            $wallet->save();

            $record = new PaymentRecord;
            $record->user_id = $order->user_id;
            $record->order_id = $order->id;
            $record->type = PaymentType::PAY->value;
            $record->amount = $order->total_amount;
            $record->gateway = $this->type();
            $record->status = 'success';
            $record->save();

            $order->transitionTo(OrderStatus::PAID);

            return $record;
        });
    }
}
