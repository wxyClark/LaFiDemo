<?php

use App\Modules\Domains\Address\Support\CanonicalCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'area_code')) {
                $table->string('area_code', 12)->nullable()->comment('民政部标准行政区划码: 省/市/区6位, 乡镇9位, 跨平台统一身份');
            }
            if (! Schema::hasColumn('addresses', 'source')) {
                $table->string('source', 30)->default('pcas')->comment('数据来源: pcas/amap/...');
            }
            if (! Schema::hasColumn('addresses', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->comment('最近一次数据同步时间');
            }
        });

        // 回填 area_code（按 level_num 补零到 6/9 位）
        $rows = DB::table('addresses')->select('id', 'code', 'level_num')->get()->all();

        foreach (array_chunk($rows, 1000) as $chunk) {
            $cases = '';
            $ids = [];
            foreach ($chunk as $row) {
                $cases .= "WHEN {$row->id} THEN '".CanonicalCode::normalize($row->code, $row->level_num)."' ";
                $ids[] = $row->id;
            }

            DB::statement(
                "UPDATE addresses SET area_code = CASE id {$cases}END WHERE id IN (".implode(',', $ids).')'
            );
        }

        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasIndex('addresses', 'addresses_area_code_index')) {
                $table->index('area_code');
            }
            if (! Schema::hasIndex('addresses', 'addresses_source_index')) {
                $table->index('source');
            }
        });

        $this->ensureAreaCodeUnique();
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['area_code']);
            $table->dropIndex(['source']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['area_code', 'source', 'synced_at']);
        });
    }

    /**
     * SQLite 支持唯一索引的 nullable 列（NULL 不参与唯一性）
     */
    private function ensureAreaCodeUnique(): void
    {
        $table = Schema::getConnection()->getTablePrefix().'addresses';
        $count = DB::table('addresses')->selectRaw('area_code, count(*) as c')->groupBy('area_code')->whereNotNull('area_code')->havingRaw('count(*) > 1')->count();

        if ($count > 0) {
            throw new RuntimeException("addresses 表存在重复 area_code ({$count} 组)，无法建立唯一约束，请检查数据");
        }

        $driver = DB::connection()->getDriverName();
        $indexName = "idx_{$table}_area_code_unique";

        // MySQL does not support "CREATE INDEX IF NOT EXISTS"; other drivers do.
        $sql = match ($driver) {
            'sqlite', 'pgsql' => "CREATE UNIQUE INDEX IF NOT EXISTS {$indexName} ON {$table} (area_code)",
            default => "CREATE UNIQUE INDEX {$indexName} ON {$table} (area_code)",
        };

        DB::statement($sql);
    }
};
