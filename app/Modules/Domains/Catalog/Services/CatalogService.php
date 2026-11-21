<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Services;

use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Catalog\Models\BundleItem;
use App\Modules\Domains\Catalog\Models\Product;
use App\Modules\Domains\Catalog\Models\ProductBundle;
use App\Modules\Domains\Catalog\Models\Sku;
use Illuminate\Support\Facades\Cache;

class CatalogService
{
    public function createProduct(array $data): Product
    {
        $product = new Product;
        $product->name = $data['name'];
        $product->type = ($data['type'] ?? ProductType::ENTITY)->value;
        $product->status = ($data['status'] ?? ProductStatus::ACTIVE)->value;
        $product->category_id = $data['category_id'] ?? null;
        $product->description = $data['description'] ?? null;
        $product->save();

        return $product;
    }

    public function addSku(Product $product, array $data): Sku
    {
        $sku = new Sku;
        $sku->product_id = $product->id;
        $sku->specs = $data['specs'] ?? null;
        $sku->price = $data['price'];
        $sku->stock = $data['stock'] ?? 0;
        $sku->status = ($data['status'] ?? ProductStatus::ACTIVE)->value;
        $sku->save();

        return $sku;
    }

    public function createBundle(string $name, string $price, array $items): ProductBundle
    {
        $bundle = new ProductBundle;
        $bundle->name = $name;
        $bundle->price = $price;
        $bundle->status = ProductStatus::ACTIVE->value;
        $bundle->save();

        foreach ($items as $item) {
            $row = new BundleItem;
            $row->bundle_id = $bundle->id;
            $row->sku_id = $item['sku_id'];
            $row->qty = $item['qty'] ?? 1;
            $row->save();
        }

        return $bundle->load('items');
    }

    public function decrementStock(Sku $sku, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        if ($sku->stock < $qty) {
            throw new \RuntimeException("SKU #{$sku->id} 库存不足");
        }

        $sku->decrementStock($qty);
    }

    public function activeList(): array
    {
        return Cache::remember('catalog.products.active', now()->addHour(), function () {
            return Product::active()->with('skus')->get()->toArray();
        });
    }
}
