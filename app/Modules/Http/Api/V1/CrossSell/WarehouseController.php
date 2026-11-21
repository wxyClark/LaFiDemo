<?php

declare(strict_types=1);

namespace App\Modules\Http\Api\V1\CrossSell;

use App\Modules\CrossSell\Warehouse\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends BaseCrossSellController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        $warehouses = Warehouse::where('tenant_id', $tenantId)
            ->when($request->is_active !== null, fn ($q, $isActive) => $q->where('is_active', $isActive))
            ->paginate($request->per_page ?? 15);

        return $this->paginated($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['tenant_id'] = $this->getTenantId($request);

        return $this->createModel(Warehouse::class, $validated, '仓库创建成功');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $warehouse = $this->findByIdOrError(Warehouse::class, $id, '仓库不存在');

        if (! $warehouse) {
            return $this->notFound('仓库不存在');
        }

        return $this->success($warehouse->toArray());
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $warehouse = $this->findByIdOrError(Warehouse::class, $id, '仓库不存在');

        if (! $warehouse) {
            return $this->notFound('仓库不存在');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        return $this->updateModel($warehouse, $validated, '仓库更新成功');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $warehouse = $this->findByIdOrError(Warehouse::class, $id, '仓库不存在');

        if (! $warehouse) {
            return $this->notFound('仓库不存在');
        }

        return $this->deleteModel($warehouse, '仓库删除成功');
    }
}
