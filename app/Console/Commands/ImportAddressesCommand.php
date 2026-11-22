<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Domains\Address\Services\AddressImportService;
use Illuminate\Console\Command;

class ImportAddressesCommand extends Command
{
    protected $signature = 'address:import
        {--source=mca : 数据源（mca=民政部/国家地名信息库）}
        {--max-level=3 : 查询层级深度（1-3=省市区县；4=含乡镇，需逐县级下钻）}
        {--year= : 指定年份（缺省为接口最新年版）}
        {--throttle-ms= : 下钻请求间隔毫秒（缺省取自 config，350）}
        {--throttle-max-ms= : 失败退避间隔上限毫秒（缺省取自 config，5000）}
        {--drill-limit= : 仅下钻前 N 个县级单位（测试/小批，缺省全部）}
        {--dry-run : 仅拉取并记录接口原始数据，不写入 addresses}';

    protected $description = '从民政部/国家地名信息库拉取行政区划（编号/名称/层级/上下级），接口原始数据落库并导入 addresses';

    public function handle(AddressImportService $service): int
    {
        $source = (string) $this->option('source');
        if ($source !== 'mca') {
            $this->error("暂不支持的数据源：{$source}（当前仅 mca=民政部/国家地名信息库）");

            return self::FAILURE;
        }

        $maxLevel = (int) $this->option('max-level');
        $year = $this->option('year') ? (int) $this->option('year') : null;
        $dryRun = (bool) $this->option('dry-run');
        $delayMs = $this->option('throttle-ms') ? (int) $this->option('throttle-ms') : null;
        $maxDelayMs = $this->option('throttle-max-ms') ? (int) $this->option('throttle-max-ms') : null;
        $drillLimit = $this->option('drill-limit') ? (int) $this->option('drill-limit') : null;

        $result = $service->import($maxLevel, $year, $dryRun, $delayMs, $maxDelayMs, $drillLimit, function (int $done, int $total, int $rowsSoFar) {
            $this->line(sprintf('  下钻进度 %d/%d（%d%%）  累计乡镇节点 %d', $done, $total, $total > 0 ? (int) round($done / $total * 100) : 0, $rowsSoFar));
        });

        $msg = sprintf(
            '日志ID: %d  状态: %s  解析节点: %d  写入 addresses: %d',
            $result['log_id'],
            $result['status'],
            $result['nodes'],
            $result['imported'],
        );
        if ($result['drill'] > 0) {
            $msg .= sprintf('  下钻: %d 次', $result['drill']);
        }
        $this->info($msg);

        if ($result['status'] === 'failed') {
            $this->error('拉取/解析失败，请查看 address_import_logs 表对应日志（error_message）。');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('dry-run：未写入 addresses（已记录原始数据）。');

            return self::SUCCESS;
        }

        return self::SUCCESS;
    }
}
