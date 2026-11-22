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
 *
 * 乡镇级（level=4）：接口在省级查询不返回乡镇，需逐县级下钻（maxLevel=1）；
 * 不设区的市（东莞/中山等，level=2 且无区县子级）需 maxLevel=2。请求间隔自适应：
 * 从基础值开始，遇 429/错误按比例增大（从小到大试探）直到上限。
 */
class AddressImportService
{
    private int $sortCounter = 0;

    public function __construct(private readonly AddressService $addressService) {}

    /**
     * 拉取 + 记录原始数据 + 导入。
     *
     * @return array{log_id:int,status:string,nodes:int,imported:int,drill:int}
     */
    public function import(
        int $maxLevel = 3,
        ?int $year = null,
        bool $dryRun = false,
        ?int $delayMs = null,
        ?int $maxDelayMs = null,
        ?int $drillLimit = null,
    ): array {
        // 根查询对"省级入口"封顶于区县（maxLevel≥4 返回与 3 相同的省市区县树），
        // 乡镇级需逐县级下钻，故基础树固定按 3 层抓取，层级>=4 时另行 drill。
        $log = $this->fetchAndRecord(min($maxLevel, 3), $year);

        if ($log->status === 'failed') {
            return $this->failed($log->id);
        }

        $nodes = $this->extractNodes($log->response_raw);
        if ($nodes === null) {
            $log->update(['status' => 'failed', 'error_message' => '无法从响应中解析行政区划节点（接口结构可能变化）']);

            return $this->failed($log->id);
        }

        $rows = $this->flatten($nodes);
        $drillRecords = [];
        $usedDelay = $delayMs ?? (int) config('services.address.mca_delay_ms', 350);

        if ($maxLevel >= 4) {
            [$drillRows, $drillRecords, $usedDelay] = $this->drill(
                $this->drillTargets($nodes, $drillLimit),
                $year,
                $delayMs,
                $maxDelayMs,
            );
            $rows = array_merge($rows, $drillRows);
        }

        $log->update([
            'node_count' => count($rows),
            'drill_count' => count($drillRecords),
            'throttle_ms' => $usedDelay,
            'drill_raw' => $drillRecords === [] ? null : json_encode($drillRecords, JSON_UNESCAPED_UNICODE),
        ]);

        if ($dryRun) {
            $log->update(['status' => 'success']);

            return ['log_id' => $log->id, 'status' => 'success', 'nodes' => count($rows), 'imported' => 0, 'drill' => count($drillRecords)];
        }

        $imported = $this->addressService->importAddresses($rows, 'mca');
        $log->update(['status' => 'success', 'imported_count' => $imported]);

        return ['log_id' => $log->id, 'status' => 'success', 'nodes' => count($rows), 'imported' => $imported, 'drill' => count($drillRecords)];
    }

    protected function fetchAndRecord(int $maxLevel, ?int $year): AddressImportLog
    {
        $params = [
            'code' => '',
            'maxLevel' => $maxLevel,
            'year' => $year,
        ];

        $log = AddressImportLog::create([
            'source' => 'mca',
            'endpoint' => $this->url(),
            'params' => $params,
            'status' => 'pending',
            'fetched_at' => now(),
        ]);

        $res = $this->fetch('', $maxLevel, $year);

        $log->update([
            'http_status' => $res['http_status'],
            'response_raw' => $res['raw'],
            'response_size' => strlen($res['raw']),
        ]);

        if (! $res['success']) {
            $log->update(['status' => 'failed', 'error_message' => $res['error']]);
        }

        return $log->fresh();
    }

    /**
     * 下钻目标：县级(level=3) 用 maxLevel=1 得乡镇；不设区的市(level=2 无子级) 用 maxLevel=2。
     *
     * @param  array<int, array>  $nodes
     * @return array<int, array{code:string,maxLevel:int,parent:string}>
     */
    protected function drillTargets(array $nodes, ?int $limit): array
    {
        $targets = [];

        $walk = function (array $nodes) use (&$walk, &$targets): void {
            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $code = isset($node['code']) ? (string) $node['code'] : '';
                $level = isset($node['level']) ? (int) $node['level'] : 0;
                if ($code === '') {
                    continue;
                }

                if ($level === 3) {
                    $targets[] = ['code' => $code, 'maxLevel' => 1, 'parent' => $code];
                } elseif ($level === 2 && empty($node['children'])) {
                    $targets[] = ['code' => $code, 'maxLevel' => 2, 'parent' => $code];
                }

                if (! empty($node['children']) && is_array($node['children'])) {
                    $walk($node['children']);
                }
            }
        };

        $walk($nodes);

        if ($limit !== null && $limit > 0) {
            $targets = array_slice($targets, 0, $limit);
        }

        return $targets;
    }

    /**
     * 逐目标下钻，自适应退避，逐次记录原始响应。
     *
     * @param  array<int, array{code:string,maxLevel:int,parent:string}>  $targets
     * @return array{0:array<int,array>,1:array<int,array>,2:int}
     */
    protected function drill(array $targets, ?int $year, ?int $delayMs, ?int $maxDelayMs): array
    {
        $delay = $delayMs ?? (int) config('services.address.mca_delay_ms', 350);
        $maxDelay = $maxDelayMs ?? (int) config('services.address.mca_max_delay_ms', 5000);
        $rows = [];
        $records = [];

        foreach ($targets as $i => $target) {
            if ($i > 0 && $delay > 0) {
                usleep($delay * 1000);
            }

            $res = $this->fetch($target['code'], $target['maxLevel'], $year);

            $record = [
                'code' => $target['code'],
                'maxLevel' => $target['maxLevel'],
                'http_status' => $res['http_status'],
                'node_count' => $res['nodes'] !== null ? count($res['nodes']) : 0,
                'error' => $res['error'],
            ];

            if ($res['success'] && $res['nodes'] !== null) {
                $record['raw'] = $res['raw'];

                foreach ($res['nodes'] as $child) {
                    $row = $this->row($child, $target['parent']);
                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }

                // 成功：保持当前间隔（稳定）；失败：增大（从小到大试探）
            } else {
                $delay = min($maxDelay, (int) round($delay * 1.5));
            }

            $records[] = $record;
        }

        return [$rows, $records, $delay];
    }

    /**
     * @return array{http_status:?int,raw:string,nodes:?array<int,array>,success:bool,error:?string}
     */
    protected function fetch(string $code, int $maxLevel, ?int $year): array
    {
        $params = [
            'code' => $code,
            'maxLevel' => $maxLevel,
            'year' => $year,
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->withOptions(['verify' => (bool) config('services.address.mca_verify', true)])
                ->get($this->url(), array_filter($params, fn ($v) => $v !== null));

            $raw = $response->body();
            $nodes = null;
            $error = null;

            if (! $response->successful()) {
                $error = 'HTTP '.$response->status();
            } elseif (trim($raw) === '') {
                $error = '响应体为空（可能被 WAF 拦截，请核对请求头）';
            } else {
                $nodes = $this->extractNodes($raw);
                if ($nodes === null) {
                    $error = '无法从响应中解析行政区划节点（接口结构可能变化）';
                }
            }

            return [
                'http_status' => $response->status(),
                'raw' => $raw,
                'nodes' => $nodes,
                'success' => $error === null,
                'error' => $error,
            ];
        } catch (\Throwable $e) {
            return ['http_status' => null, 'raw' => '', 'nodes' => null, 'success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function url(): string
    {
        $base = (string) config('services.address.mca_base_url');
        $endpoint = (string) config('services.address.mca_xzqh_endpoint');

        return rtrim($base, '/').'/'.ltrim($endpoint, '/');
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
    protected function flatten(array $nodes, ?string $parentCode = null): array
    {
        $rows = [];

        foreach ($nodes as $node) {
            $row = $this->row($node, $parentCode);
            if ($row === null) {
                continue;
            }

            $rows[] = $row;

            if (! empty($node['children']) && is_array($node['children'])) {
                $rows = array_merge($rows, $this->flatten($node['children'], $row['code']));
            }
        }

        return $rows;
    }

    /**
     * @return array{parent_code:?string,code:string,name:string,level:string,level_num:int,sort:int}|null
     */
    protected function row(array $node, ?string $parentCode): ?array
    {
        $code = isset($node['code']) ? (string) $node['code'] : '';
        $name = isset($node['name']) ? (string) $node['name'] : '';
        if ($code === '' || $name === '') {
            return null;
        }

        $levelNum = isset($node['level']) ? (int) $node['level'] : 1;

        return [
            'parent_code' => $parentCode,
            'code' => $code,
            'name' => $name,
            'level' => $this->levelLabel($levelNum),
            'level_num' => $levelNum,
            'sort' => $this->sortCounter++,
        ];
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

    /**
     * @return array{log_id:int,status:string,nodes:int,imported:int,drill:int}
     */
    protected function failed(int $logId): array
    {
        return ['log_id' => $logId, 'status' => 'failed', 'nodes' => 0, 'imported' => 0, 'drill' => 0];
    }
}
