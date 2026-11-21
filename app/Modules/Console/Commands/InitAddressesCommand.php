<?php

declare(strict_types=1);

namespace App\Modules\Console\Commands;

use App\Modules\Services\AddressImport\AddressImporter;
use App\Modules\Services\AddressImport\Providers\AmapProvider;
use App\Modules\Services\AddressImport\Providers\PcasRemoteProvider;
use Illuminate\Console\Command;

class InitAddressesCommand extends Command
{
    protected $signature = 'addresses:init {--source=pcas : 数据源: pcas(Gitee免key) / amap(高德, 需 AMAP_KEY)} {--flush : 清空地址表与断点状态后全量重导} {--unit= : 仅导入指定省级单元(如 44)}';

    protected $description = '从远端拉取最新行政区划数据并分页入库（支持断点续传、多平台来源）';

    public function handle(): int
    {
        $source = $this->option('source');

        $provider = match ($source) {
            'pcas' => new PcasRemoteProvider,
            'amap' => new AmapProvider,
            default => null,
        };

        if ($provider === null) {
            $this->error("未知数据源: {$source}（可选 pcas / amap）");

            return Command::FAILURE;
        }

        try {
            $revision = $provider->revision();
        } catch (\Throwable $e) {
            $this->error('数据源初始化失败: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('数据源: '.$provider->name()." (修订号 {$revision})");

        if ($this->option('flush')) {
            $this->warn('--flush: 将清空 addresses 表并重置断点状态');
        }

        $importer = new AddressImporter($provider);
        $stats = $importer->import($this->option('unit'), (bool) $this->option('flush'));

        $this->info(
            "完成: 新导 {$stats['imported']} 页 / 跳过 {$stats['skipped']} 页 / 共 {$stats['rows']} 行"
        );

        return Command::SUCCESS;
    }
}
