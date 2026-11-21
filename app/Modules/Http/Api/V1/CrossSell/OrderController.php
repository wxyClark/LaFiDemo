<?php

declare(strict_types=1);

namespace App\Modules\Http\Api\V1\CrossSell;

use App\Modules\Domains\CrossSell\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends BaseCrossSellController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        $orders = Order::where('tenant_id', $tenantId)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->platform, fn ($q, $platform) => $q->where('platform', $platform))
            ->with('items')
            ->paginate($request->per_page ?? 15);

        return $this->paginated($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_no' => 'required|string|max:50|unique:cross_sell_orders,order_no',
            'platform' => 'required|string|in:temu,shopify,amazon,shoplazza,aliexpress,manual',
            'status' => 'nullable|string|in:pending,confirmed,processing,shipped,delivered,completed,cancelled,refunded',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_email' => 'nullable|email|max:255',
            'buyer_phone' => 'nullable|string|max:50',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|array',
            'shipping_country' => 'nullable|string|max:10',
            'subtotal' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'note' => 'nullable|string',
            'customer_note' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $this->getTenantId($request);
        $validated['status'] = $validated['status'] ?? 'pending';
        $validated['currency'] = $validated['currency'] ?? 'USD';

        return $this->createModel(Order::class, $validated, '订单创建成功');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = $this->findByIdOrError(Order::class, $id, '订单不存在');

        if (! $order) {
            return $this->notFound('订单不存在');
        }

        $order->load(['items.sku', 'refunds', 'shipments']);

        return $this->success(array_merge($order->getAttributes(), $order->relationsToArray()));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $order = $this->findByIdOrError(Order::class, $id, '订单不存在');

        if (! $order) {
            return $this->notFound('订单不存在');
        }

        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,confirmed,processing,shipped,delivered,completed,cancelled,refunded',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_email' => 'nullable|email|max:255',
            'buyer_phone' => 'nullable|string|max:50',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|array',
            'shipping_country' => 'nullable|string|max:10',
            'note' => 'nullable|string',
            'customer_note' => 'nullable|string',
        ]);

        return $this->updateModel($order, $validated, '订单更新成功');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $order = $this->findByIdOrError(Order::class, $id, '订单不存在');

        if (! $order) {
            return $this->notFound('订单不存在');
        }

        return $this->deleteModel($order, '订单删除成功');
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $order = $this->findByIdOrError(Order::class, $id, '订单不存在');

        if (! $order) {
            return $this->notFound('订单不存在');
        }

        return $this->performAction($order, 'confirm', '订单确认成功', '订单状态不允许确认');
    }

    public function ship(Request $request, string $id): JsonResponse
    {
        $order = $this->findByIdOrError(Order::class, $id, '订单不存在');

        if (! $order) {
            return $this->notFound('订单不存在');
        }

        $request->validate([
            'tracking_no' => 'nullable|string|max:100',
        ]);

        return $this->performAction($order, 'ship', '订单发货成功', '订单状态不允许发货');
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $order = $this->findByIdOrError(Order::class, $id, '订单不存在');

        if (! $order) {
            return $this->notFound('订单不存在');
        }

        return $this->performAction($order, 'cancel', '订单取消成功', '订单状态不允许取消');
    }
}
