<?php

declare(strict_types=1);

namespace App\Modules\Services;

use App\Modules\Domains\CrossSell\Enums\OrderStatus;
use App\Modules\Domains\CrossSell\Enums\PlatformType;
use App\Modules\Domains\CrossSell\Enums\ProductStatus;
use App\Modules\Domains\CrossSell\Models\Inventory;
use App\Modules\Domains\CrossSell\Models\Order;
use App\Modules\Domains\CrossSell\Models\Product;
use App\Modules\Domains\CrossSell\Models\SKU;
use App\Modules\Domains\CrossSell\Models\Warehouse;
use App\Modules\Models\Address;
use App\Modules\Models\Customer;
use Illuminate\Support\Collection;

class ImportService
{
    private const PRODUCT_COLUMNS = [
        'title' => ['required' => true, 'label' => '商品标题'],
        'brand' => ['required' => false, 'label' => '品牌'],
        'category_id' => ['required' => false, 'label' => '分类ID', 'type' => 'integer'],
        'status' => ['required' => false, 'label' => '状态', 'type' => 'enum', 'values' => ['draft', 'active', 'inactive'], 'enum_class' => ProductStatus::class],
        'description' => ['required' => false, 'label' => '描述'],
        'sku' => ['required' => false, 'label' => 'SKU编码'],
        'price' => ['required' => false, 'label' => '价格', 'type' => 'decimal'],
        'stock' => ['required' => false, 'label' => '库存', 'type' => 'integer'],
    ];

    private const ORDER_COLUMNS = [
        'order_no' => ['required' => true, 'label' => '订单号'],
        'platform' => ['required' => true, 'label' => '平台', 'type' => 'enum', 'values' => ['temu', 'shopify', 'amazon', 'shoplazza', 'aliexpress', 'manual'], 'enum_class' => PlatformType::class],
        'buyer_name' => ['required' => false, 'label' => '买家姓名'],
        'buyer_email' => ['required' => false, 'label' => '买家邮箱'],
        'total' => ['required' => true, 'label' => '订单金额', 'type' => 'decimal'],
        'currency' => ['required' => false, 'label' => '币种', 'default' => 'USD'],
        'status' => ['required' => false, 'label' => '状态', 'type' => 'enum', 'values' => ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'], 'enum_class' => OrderStatus::class],
        'shipping_fee' => ['required' => false, 'label' => '运费', 'type' => 'decimal'],
        'tax' => ['required' => false, 'label' => '税费', 'type' => 'decimal'],
        'note' => ['required' => false, 'label' => '备注'],
    ];

    public function importProducts(string $filePath, int $tenantId): array
    {
        $rows = $this->parseCsv($filePath);
        $headers = $rows->shift() ?? collect();

        $this->validateHeaders($headers, self::PRODUCT_COLUMNS);

        $created = 0;
        $updated = 0;
        $errors = [];

        $headerMap = $this->buildHeaderMap($headers, self::PRODUCT_COLUMNS);

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            try {
                $data = $this->mapRowToData($row, $headerMap, self::PRODUCT_COLUMNS);
                $data['tenant_id'] = $tenantId;

                $skuData = [
                    'sku' => $data['sku'] ?? null,
                    'price' => $data['price'] ?? 0,
                    'stock' => $data['stock'] ?? 0,
                ];
                $productData = collect($data)->except(['sku', 'price', 'stock'])->toArray();

                $existing = Product::where('tenant_id', $tenantId)
                    ->where('title', $data['title'])
                    ->first();

                if ($existing) {
                    $existing->update(collect($productData)->except('tenant_id')->toArray());
                    $updated++;
                } else {
                    $product = Product::create($productData);

                    if (! empty($skuData['sku'])) {
                        SKU::create([
                            'tenant_id' => $tenantId,
                            'product_id' => $product->id,
                            'sku_code' => $skuData['sku'],
                            'price' => $skuData['price'],
                            'stock' => $skuData['stock'],
                            'available_stock' => $skuData['stock'],
                        ]);
                    }

                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "第 {$lineNum} 行: {$e->getMessage()}";
            }
        }

        return [
            'total' => $created + $updated + count($errors),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function importOrders(string $filePath, int $tenantId): array
    {
        $rows = $this->parseCsv($filePath);
        $headers = $rows->shift() ?? collect();

        $this->validateHeaders($headers, self::ORDER_COLUMNS);

        $created = 0;
        $skipped = 0;
        $errors = [];

        $headerMap = $this->buildHeaderMap($headers, self::ORDER_COLUMNS);

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            try {
                $data = $this->mapRowToData($row, $headerMap, self::ORDER_COLUMNS);
                $data['tenant_id'] = $tenantId;

                if (Order::where('tenant_id', $tenantId)->where('order_no', $data['order_no'])->exists()) {
                    $skipped++;

                    continue;
                }

                Order::create($data);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "第 {$lineNum} 行: {$e->getMessage()}";
            }
        }

        return [
            'total' => $created + $skipped + count($errors),
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function generateProductTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'product_import_');
        $file = fopen($tempFile, 'w');

        fputcsv($file, ['title', 'brand', 'category_id', 'status', 'description', 'sku', 'price', 'stock']);
        fputcsv($file, ['示例商品', '品牌名', '1', 'active', '商品描述', 'SKU001', '99.99', '100']);
        fputcsv($file, ['iPhone 15', 'Apple', '2', 'draft', '最新款 iPhone', 'IP15-001', '799.00', '50']);

        fclose($file);

        return $tempFile;
    }

    public function generateOrderTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'order_import_');
        $file = fopen($tempFile, 'w');

        fputcsv($file, ['order_no', 'platform', 'buyer_name', 'buyer_email', 'total', 'currency', 'status', 'shipping_fee', 'tax', 'note']);
        fputcsv($file, ['ORD-20260725-001', 'temu', '张三', 'zhangsan@example.com', '199.99', 'USD', 'pending', '5.00', '0.00', '']);
        fputcsv($file, ['ORD-20260725-002', 'shopify', 'John', 'john@example.com', '89.50', 'USD', 'confirmed', '0.00', '7.50', '加急']);

        fclose($file);

        return $tempFile;
    }

    private function parseCsv(string $filePath): Collection
    {
        $rows = collect();
        $file = fopen($filePath, 'r');

        if ($file === false) {
            throw new \RuntimeException('无法读取文件: '.$filePath);
        }

        while (($row = fgetcsv($file)) !== false) {
            $rows->push(collect($row));
        }

        fclose($file);

        return $rows;
    }

    private function validateHeaders(Collection $headers, array $columns): void
    {
        $required = array_filter($columns, fn ($col) => $col['required']);
        $requiredKeys = array_keys($required);
        $headerKeys = array_map('strtolower', $headers->map(fn ($h) => trim((string) $h))->toArray());

        foreach ($requiredKeys as $key) {
            if (! in_array($key, $headerKeys)) {
                throw new \RuntimeException("缺少必需列: {$columns[$key]['label']} ({$key})");
            }
        }
    }

    private function buildHeaderMap(Collection $headers, array $columns): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string) $header));
            if (isset($columns[$key])) {
                $map[$index] = $key;
            }
        }

        return $map;
    }

    private function mapRowToData(Collection $row, array $headerMap, array $columns): array
    {
        $data = [];

        foreach ($headerMap as $index => $key) {
            $value = $row->get($index, '');
            $colDef = $columns[$key];

            if ($value === '' || $value === null) {
                if ($colDef['required'] ?? false) {
                    throw new \RuntimeException("列 [{$colDef['label']}] 不能为空");
                }
                if (isset($colDef['default'])) {
                    $data[$key] = $colDef['default'];
                }

                continue;
            }

            $data[$key] = match ($colDef['type'] ?? 'string') {
                'integer' => (int) $value,
                'decimal' => (float) $value,
                'enum' => $this->castEnum((string) $value, $colDef['values'] ?? [], $colDef['enum_class'] ?? null),
                default => (string) $value,
            };
        }

        return $data;
    }

    private function castEnum(string $value, array $allowed, ?string $enumClass = null): string
    {
        $lower = strtolower($value);

        if ($enumClass && method_exists($enumClass, 'cases')) {
            $cases = $enumClass::cases();
            $labelMap = array_combine(
                array_map(fn ($c) => strtolower($c->label()), $cases),
                array_map(fn ($c) => $c->value, $cases),
            );

            if (isset($labelMap[$lower])) {
                return $labelMap[$lower];
            }
        }

        if (in_array($lower, $allowed)) {
            return $lower;
        }

        throw new \RuntimeException("无效的值 [{$value}]，允许: ".implode(', ', $allowed));
    }

    // ── 客户导入 ──────────────────────────────────────────

    private const CUSTOMER_COLUMNS = [
        'name' => ['required' => true, 'label' => '客户姓名'],
        'email' => ['required' => true, 'label' => '邮箱', 'type' => 'email'],
        'phone' => ['required' => false, 'label' => '手机号'],
    ];

    public function importCustomers(string $filePath): array
    {
        $rows = $this->parseCsv($filePath);
        $headers = $rows->shift() ?? collect();

        $this->validateHeaders($headers, self::CUSTOMER_COLUMNS);

        $created = 0;
        $updated = 0;
        $errors = [];

        $headerMap = $this->buildHeaderMap($headers, self::CUSTOMER_COLUMNS);

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            try {
                $data = $this->mapRowToData($row, $headerMap, self::CUSTOMER_COLUMNS);

                $existing = Customer::where('email', $data['email'])->first();

                if ($existing) {
                    $existing->update(collect($data)->except('email')->toArray());
                    $updated++;
                } else {
                    $data['password'] = 'password'; // 默认密码
                    Customer::create($data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "第 {$lineNum} 行: {$e->getMessage()}";
            }
        }

        return [
            'total' => $created + $updated + count($errors),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function generateCustomerTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'customer_import_');
        $file = fopen($tempFile, 'w');

        fputcsv($file, ['name', 'email', 'phone']);
        fputcsv($file, ['张三', 'zhangsan@example.com', '13800138000']);
        fputcsv($file, ['John Doe', 'john@example.com', '+1234567890']);

        fclose($file);

        return $tempFile;
    }

    // ── 库存导入 ──────────────────────────────────────────

    private const INVENTORY_COLUMNS = [
        'sku_code' => ['required' => true, 'label' => 'SKU编码'],
        'warehouse_name' => ['required' => true, 'label' => '仓库名称'],
        'quantity' => ['required' => true, 'label' => '数量', 'type' => 'integer'],
        'min_stock' => ['required' => false, 'label' => '最低库存', 'type' => 'integer', 'default' => 0],
        'max_stock' => ['required' => false, 'label' => '最高库存', 'type' => 'integer', 'default' => 0],
    ];

    public function importInventory(string $filePath, int $tenantId): array
    {
        $rows = $this->parseCsv($filePath);
        $headers = $rows->shift() ?? collect();

        $this->validateHeaders($headers, self::INVENTORY_COLUMNS);

        $created = 0;
        $updated = 0;
        $errors = [];

        $headerMap = $this->buildHeaderMap($headers, self::INVENTORY_COLUMNS);

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            try {
                $data = $this->mapRowToData($row, $headerMap, self::INVENTORY_COLUMNS);

                $sku = SKU::where('tenant_id', $tenantId)
                    ->where('sku_code', $data['sku_code'])
                    ->first();

                if (! $sku) {
                    $errors[] = "第 {$lineNum} 行: SKU不存在 [{$data['sku_code']}]";

                    continue;
                }

                $warehouse = Warehouse::where('tenant_id', $tenantId)
                    ->where('name', $data['warehouse_name'])
                    ->first();

                if (! $warehouse) {
                    $errors[] = "第 {$lineNum} 行: 仓库不存在 [{$data['warehouse_name']}]";

                    continue;
                }

                $existing = Inventory::where('tenant_id', $tenantId)
                    ->where('sku_id', $sku->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'quantity' => $data['quantity'],
                        'available_quantity' => $data['quantity'] - $existing->reserved_quantity,
                        'min_stock' => $data['min_stock'],
                        'max_stock' => $data['max_stock'],
                    ]);
                    $updated++;
                } else {
                    Inventory::create([
                        'tenant_id' => $tenantId,
                        'sku_id' => $sku->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => $data['quantity'],
                        'reserved_quantity' => 0,
                        'available_quantity' => $data['quantity'],
                        'min_stock' => $data['min_stock'],
                        'max_stock' => $data['max_stock'],
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "第 {$lineNum} 行: {$e->getMessage()}";
            }
        }

        return [
            'total' => $created + $updated + count($errors),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function generateInventoryTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_import_');
        $file = fopen($tempFile, 'w');

        fputcsv($file, ['sku_code', 'warehouse_name', 'quantity', 'min_stock', 'max_stock']);
        fputcsv($file, ['SKU001', '义乌仓库', '100', '10', '500']);
        fputcsv($file, ['SKU002', '义乌仓库', '50', '5', '200']);

        fclose($file);

        return $tempFile;
    }

    // ── 地址导入 ──────────────────────────────────────────

    private const ADDRESS_COLUMNS = [
        'name' => ['required' => true, 'label' => '地址名称'],
        'code' => ['required' => true, 'label' => '行政区划代码'],
        'parent_code' => ['required' => false, 'label' => '上级代码'],
        'level' => ['required' => true, 'label' => '层级', 'type' => 'enum', 'values' => ['country', 'province', 'city', 'district', 'township']],
        'pinyin' => ['required' => false, 'label' => '拼音'],
        'sort' => ['required' => false, 'label' => '排序', 'type' => 'integer', 'default' => 0],
    ];

    public function importAddresses(string $filePath): array
    {
        $rows = $this->parseCsv($filePath);
        $headers = $rows->shift() ?? collect();

        $this->validateHeaders($headers, self::ADDRESS_COLUMNS);

        $created = 0;
        $updated = 0;
        $errors = [];

        $headerMap = $this->buildHeaderMap($headers, self::ADDRESS_COLUMNS);

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2;
            try {
                $data = $this->mapRowToData($row, $headerMap, self::ADDRESS_COLUMNS);

                $existing = Address::where('code', $data['code'])->first();

                if ($existing) {
                    $existing->update(collect($data)->except('code')->toArray());
                    $updated++;
                } else {
                    $parentId = null;
                    if (! empty($data['parent_code'])) {
                        $parent = Address::where('code', $data['parent_code'])->first();
                        $parentId = $parent?->id;
                    }

                    $data['parent_id'] = $parentId;
                    $data['level_num'] = $this->getLevelNum($data['level']);
                    unset($data['parent_code']);

                    Address::create($data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "第 {$lineNum} 行: {$e->getMessage()}";
            }
        }

        return [
            'total' => $created + $updated + count($errors),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    private function getLevelNum(string $level): int
    {
        return match ($level) {
            'country' => 1,
            'province' => 2,
            'city' => 3,
            'district' => 4,
            'township' => 5,
            default => 0,
        };
    }

    public function generateAddressTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'address_import_');
        $file = fopen($tempFile, 'w');

        fputcsv($file, ['name', 'code', 'parent_code', 'level', 'pinyin', 'sort']);
        fputcsv($file, ['中国', '100000', '', 'country', 'zhongguo', '0']);
        fputcsv($file, ['北京市', '110000', '100000', 'province', 'beijing', '1']);
        fputcsv($file, ['北京市', '110100', '110000', 'city', 'beijing', '1']);

        fclose($file);

        return $tempFile;
    }
}
