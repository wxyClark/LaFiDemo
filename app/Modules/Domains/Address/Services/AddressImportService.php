<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Services;

use App\Modules\Domains\Address\Enums\AddressLevel;
use App\Modules\Domains\Address\Models\AddressImportLog;
use Illuminate\Support\Facades\Http;

/**
 * 从民政部 · 中国·国家地名信息库（dmfw.mca.gov.cn）拉取行政区划并写入 addresses。
 *
 * 接口：GET /xzqh/getList?code=&maxLevel=N[&year=Y]
 * 返回嵌套树，节点含 code(12位)/name/level/type/children。
 * 注意：直辖市/不设区的市存在层级跳跃（如北京市直接挂 level=3 的市辖区），
 * 故层级一律取节点自身 level 字段，不得按嵌套深度推断；上下级关系取自树结构。
 */
class AddressImportService
{
    public function __construct(private readonly AddressService $addressService) {}

    /**
     * 拉取 + 记录原始数据 + 导入。
     *
     * @return array{log_id:int,status:string,nodes:int,imported:int}
     */
    public function import(int $maxLevel = 3, ?int $year = null, bool $dryRun = false): array
    {
        $log = $this->fetchAndRecord($maxLevel, $year);

        if ($log->status === 'failed') {
            return ['log_id' => $log->id, 'status' => 'failed', 'nodes' => 0, 'imported' => 0];
        }

        $nodes = $this->extractNodes($log->response_raw);
        if ($nodes === null) {
            $log->update(['status' => 'failed', 'error_message' => '无法从响应中解析行政区划节点（接口结构可能变化）']);

            return ['log_id' => $log->id, 'status' => 'failed', 'nodes' => 0, 'imported' => 0];
        }

        $rows = $this->flatten($nodes);
        $log->update(['node_count' => count($rows)]);

        if ($dryRun) {
            $log->update(['status' => 'success']);

            return ['log_id' => $log->id, 'status' => 'success', 'nodes' => count($rows), 'imported' => 0];
        }

        $imported = $this->addressService->importAddresses($rows, 'mca');
        $log->update(['status' => 'success', 'imported_count' => $imported]);

        return ['log_id' => $log->id, 'status' => 'success', 'nodes' => count($rows), 'imported' => $imported];
    }

    protected function fetchAndRecord(int $maxLevel, ?int $year): AddressImportLog
    {
        $base = (string) config('services.address.mca_base_url');
        $endpoint = (string) config('services.address.mca_xzqh_endpoint');
        $url = rtrim($base, '/').'/'.ltrim($endpoint, '/');

        $params = [
            'code' => '',
            'maxLevel' => $maxLevel,
            'year' => $year,
        ];

        $log = AddressImportLog::create([
            'source' => 'mca',
            'endpoint' => $url,
            'params' => $params,
            'status' => 'pending',
            'fetched_at' => now(),
        ]);

        try {
            $response = Http::withHeaders($this->headers())
                ->withOptions(['verify' => (bool) config('services.address.mca_verify', true)])
                ->get($url, array_filter($params, fn ($v) => $v !== null));

            $raw = $response->body();
            $log->update([
                'http_status' => $response->status(),
                'response_raw' => $raw,
                'response_size' => strlen($raw),
            ]);

            if (! $response->successful()) {
                $log->update(['status' => 'failed', 'error_message' => 'HTTP '.$response->status()]);
            } elseif (trim($raw) === '') {
                $log->update(['status' => 'failed', 'error_message' => '响应体为空（可能被 WAF 拦截，请核对请求头）']);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return $log->fresh();
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'Referer' => 'https://dmfw.mca.gov.cn/',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json',
        ];
    }

    /**
     * 从原始响应中提取行政区划节点数组，兼容多种返回形状。
     *
     * @return array<int, array>|null
     */
    protected function extractNodes(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        foreach ([['data', 'children'], ['response', 'data', 'children'], ['data'], ['children']] as $path) {
            $node = $decoded;
            $ok = true;
            foreach ($path as $key) {
                if (! is_array($node) || ! array_key_exists($key, $node)) {
                    $ok = false;
                    break;
                }
                $node = $node[$key];
            }
            if ($ok && is_array($node)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * 将嵌套树拍平为 importAddresses 所需行（编号/名称/层级/上下级）。
     *
     * @param  array<int, array>  $nodes
     * @return array<int, array{parent_code:?string,code:string,name:string,level:string,level_num:int,sort:int}>
     */
    protected function flatten(array $nodes, ?string $parentCode = null, int &$counter = 0): array
    {
        $rows = [];

        foreach ($nodes as $node) {
            $code = isset($node['code']) ? (string) $node['code'] : '';
            $name = isset($node['name']) ? (string) $node['name'] : '';
            if ($code === '' || $name === '') {
                continue;
            }

            $levelNum = isset($node['level']) ? (int) $node['level'] : 1;

            $rows[] = [
                'parent_code' => $parentCode,
                'code' => $code,
                'name' => $name,
                'level' => $this->levelLabel($levelNum),
                'level_num' => $levelNum,
                'sort' => $counter++,
            ];

            if (! empty($node['children']) && is_array($node['children'])) {
                $rows = array_merge($rows, $this->flatten($node['children'], $code, $counter));
            }
        }

        return $rows;
    }

    protected function levelLabel(int $levelNum): string
    {
        return match ($levelNum) {
            1 => AddressLevel::PROVINCE->value,
            2 => AddressLevel::CITY->value,
            3 => AddressLevel::DISTRICT->value,
            default => AddressLevel::TOWNSHIP->value,
        };
    }
}
