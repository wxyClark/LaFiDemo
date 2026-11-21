<?php

namespace App\Modules\Console\Commands;

use App\Modules\Services\AddressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportAddressesCommand extends Command
{
    protected $signature = 'addresses:import {file?}';

    protected $description = '导入行政区划数据';

    public function handle(AddressService $service): int
    {
        $filePath = $this->argument('file') ?? database_path('seeders/addresses.json');

        if (! File::exists($filePath)) {
            $this->error("文件不存在: {$filePath}");

            return Command::FAILURE;
        }

        $this->info("正在读取文件: {$filePath}");
        $data = json_decode(File::get($filePath), true);

        if (! is_array($data)) {
            $this->error('文件格式错误');

            return Command::FAILURE;
        }

        $this->info('开始导入数据...');
        $count = $service->importAddresses($data);

        $this->info("成功导入 {$count} 条地址数据");

        return Command::SUCCESS;
    }
}
