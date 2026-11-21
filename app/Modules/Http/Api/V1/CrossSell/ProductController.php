<?php

declare(strict_types=1);

namespace App\Modules\Http\Api\V1\CrossSell;

use App\Modules\CrossSell\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseCrossSellController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        $products = Product::where('tenant_id', $tenantId)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category_id, fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->with('skus')
            ->paginate($request->per_page ?? 15);

        return $this->paginated($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:cross_sell_product_categories,id',
            'status' => 'nullable|string|in:draft,active,inactive',
            'main_image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'source_url' => 'nullable|url|max:500',
            'source_id' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        $validated['tenant_id'] = $this->getTenantId($request);
        $validated['status'] = $validated['status'] ?? 'draft';

        return $this->createModel(Product::class, $validated, '商品创建成功');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $product = $this->findByIdOrError(Product::class, $id, '商品不存在');

        if (! $product) {
            return $this->notFound('商品不存在');
        }

        $product->load(['skus', 'translations', 'category']);

        return $this->success(array_merge($product->getAttributes(), $product->relationsToArray()));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = $this->findByIdOrError(Product::class, $id, '商品不存在');

        if (! $product) {
            return $this->notFound('商品不存在');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:cross_sell_product_categories,id',
            'status' => 'sometimes|string|in:draft,active,inactive',
            'main_image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'source_url' => 'nullable|url|max:500',
            'source_id' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        return $this->updateModel($product, $validated, '商品更新成功');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $product = $this->findByIdOrError(Product::class, $id, '商品不存在');

        if (! $product) {
            return $this->notFound('商品不存在');
        }

        return $this->deleteModel($product, '商品删除成功');
    }
}
