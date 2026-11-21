<?php

declare(strict_types=1);

namespace App\Modules\Console\Commands;

use App\Modules\Models\Address;
use App\Modules\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDatabase extends Command
{
    protected $signature = 'app:reset {--snapshot : 从快照恢复地址数据（跳过 AddressSeeder，秒级完成）}';

    protected $description = '安全重置数据库：migrate:fresh + seed（替代直接运行 migrate:fresh）';

    public function handle(): int
    {
        if (! $this->confirm('即将重置数据库并重新填充数据，是否继续？')) {
            $this->info('已取消。');

            return self::SUCCESS;
        }

        $this->info('正在重置数据库...');

        // 禁用 Telescope 避免内存溢出
        putenv('TELESCOPE_ENABLED=false');

        // 增加内存限制
        ini_set('memory_limit', '512M');

        $useSnapshot = $this->option('snapshot');

        if ($useSnapshot) {
            // 快照模式：migrate:fresh 不带 seed，然后导入快照
            $exitCode = $this->call('migrate:fresh', [
                '--force' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }

            // 从快照恢复地址数据
            $snapshotPath = database_path('addresses_snapshot.sql');

            if (! file_exists($snapshotPath)) {
                $this->error("快照文件不存在: {$snapshotPath}");

                return self::FAILURE;
            }

            $this->info('正在从快照恢复地址数据...');
            $this->importSnapshot($snapshotPath);

            // 直接创建 Admin（跳过 AddressSeeder）
            $this->createAdmin();
        } else {
            // 标准模式：migrate:fresh --seed（完整 seed，耗时 2-3 分钟）
            $exitCode = $this->call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }
        }

        $this->newLine();
        $this->info('数据库重置完成！');
        $this->info('Admin: admin@example.com / password');
        $this->info('地址数据: 已导入');

        return self::SUCCESS;
    }

    private function createAdmin(): void
    {
        Admin::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    private function importSnapshot(string $snapshotPath): void
    {
        // 读取 SQL 文件
        $sql = file_get_contents($snapshotPath);

        if ($sql === false) {
            $this->error('无法读取快照文件');

            return;
        }

        // 分割成小块执行（每 1000 条 INSERT 为一批）
        $lines = explode("\n", $sql);
        $batch = [];
        $batchSize = 500;
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'INSERT')) {
                $batch[] = $line;
                $count++;

                if (count($batch) >= $batchSize) {
                    $this->executeBatch($batch);
                    $batch = [];
                }
            }
        }

        // 执行剩余的记录
        if (! empty($batch)) {
            $this->executeBatch($batch);
        }

        $total = DB::table((new Address)->getTable())->count();
        $this->info("地址数据已导入: {$total} 条记录");
    }

    private function executeBatch(array $statements): void
    {
        $sql = implode("\n", $statements);
        DB::unprepared($sql);
    }
}
