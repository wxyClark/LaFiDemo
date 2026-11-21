<?php

declare(strict_types=1);

namespace App\Modules\Http\Api\V1\CrossSell;

use App\Modules\Domains\CrossSell\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends BaseCrossSellController
{
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->plan, fn ($q, $plan) => $q->where('plan', $plan))
            ->paginate($request->per_page ?? 15);

        return $this->paginated($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:cross_sell_tenants,slug',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'plan' => 'nullable|string|in:free,basic,pro,enterprise',
            'trial_ends_at' => 'nullable|date',
        ]);

        $validated['status'] = 'active';
        $validated['trial_ends_at'] = $validated['trial_ends_at'] ?? now()->addDays(30);

        return $this->createModel(Tenant::class, $validated, '租户创建成功');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenant = $this->findByIdOrError(Tenant::class, $id, '租户不存在');

        if (! $tenant) {
            return $this->notFound('租户不存在');
        }

        return $this->success($tenant->toArray());
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = $this->findByIdOrError(Tenant::class, $id, '租户不存在');

        if (! $tenant) {
            return $this->notFound('租户不存在');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'plan' => 'sometimes|string|in:free,basic,pro,enterprise',
            'status' => 'sometimes|string|in:active,suspended,cancelled',
            'trial_ends_at' => 'nullable|date',
        ]);

        return $this->updateModel($tenant, $validated, '租户更新成功');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenant = $this->findByIdOrError(Tenant::class, $id, '租户不存在');

        if (! $tenant) {
            return $this->notFound('租户不存在');
        }

        return $this->deleteModel($tenant, '租户删除成功');
    }
}
