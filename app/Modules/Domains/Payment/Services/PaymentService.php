<?php

declare(strict_types=1);

namespace App\Modules\Domains\Payment\Services;

use App\Modules\Domains\Order\Models\Order;
use App\Modules\Domains\Payment\Enums\PaymentGatewayType;
use App\Modules\Domains\Payment\Enums\PaymentType;
use App\Modules\Domains\Payment\Gateways\BalanceGateway;
use App\Modules\Domains\Payment\Gateways\PaymentGateway;
use App\Modules\Domains\Payment\Models\PaymentRecord;
use App\Modules\Domains\Payment\Models\Wallet;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function __construct()
    {
        $this->register(new BalanceGateway);
    }

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->type()] = $gateway;
    }

    public function pay(Order $order, string $gateway = PaymentGatewayType::BALANCE->value): PaymentRecord
    {
        if (! isset($this->gateways[$gateway])) {
            throw new \RuntimeException("不支持的支付方式: {$gateway}");
        }

        return $this->gateways[$gateway]->pay($order);
    }

    public function recharge(int $userId, float $amount): PaymentRecord
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('充值金额必须大于 0');
        }

        return DB::transaction(function () use ($userId, $amount) {
            $wallet = Wallet::lockForUpdate()->firstOrCreate(['user_id' => $userId]);
            $wallet->balance = (float) $wallet->balance + $amount;
            $wallet->save();

            $record = new PaymentRecord;
            $record->user_id = $userId;
            $record->order_id = null;
            $record->type = PaymentType::RECHARGE->value;
            $record->amount = $amount;
            $record->gateway = PaymentGatewayType::BALANCE->value;
            $record->status = 'success';
            $record->save();

            return $record;
        });
    }

    public function refund(int $userId, float $amount, ?int $orderId = null): PaymentRecord
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('退款金额必须大于 0');
        }

        return DB::transaction(function () use ($userId, $amount, $orderId) {
            $wallet = Wallet::lockForUpdate()->firstOrCreate(['user_id' => $userId]);
            $wallet->balance = (float) $wallet->balance + $amount;
            $wallet->save();

            $record = new PaymentRecord;
            $record->user_id = $userId;
            $record->order_id = $orderId;
            $record->type = PaymentType::REFUND->value;
            $record->amount = $amount;
            $record->gateway = PaymentGatewayType::BALANCE->value;
            $record->status = 'success';
            $record->save();

            return $record;
        });
    }
}
