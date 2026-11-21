<?php

use App\Models\User;
use App\Modules\Dev\Models\Query;
use App\Modules\Dev\Repositories\Dev\QueryRepository;
use App\Modules\Dev\Services\Dev\QueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

uses(RefreshDatabase::class);

/**
 * 测试不会真正发送响应，download() 的 deleteFileAfterSend 不生效，手动清理。
 */
afterEach(function (): void {
    collect(File::allFiles(storage_path('app/tmp')))
        ->each(fn (string $file) => @unlink($file));
});

/**
 * Dev 查询/导出路径（来自旧单体的迁移代码）：
 * 依赖必须来自 composer.json 已声明的包（openspout），且审计表 query 真实存在。
 */
test('excel export writes a real xlsx file', function (): void {
    User::factory()->create();

    $service = new QueryService(new QueryRepository);

    $response = $service->export([
        'sql' => 'SELECT id, email FROM users',
        'type' => 'excel',
    ]);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);

    $path = $response->getFile()->getRealPath();
    expect(is_file($path))->toBeTrue()
        ->and(substr(file_get_contents($path), 0, 2))->toBe('PK');
});

test('json export streams a valid json array', function (): void {
    User::factory()->create();

    $service = new QueryService(new QueryRepository);

    $response = $service->export([
        'sql' => 'SELECT id, email FROM users',
        'type' => 'json',
    ]);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class)
        ->and(json_decode(file_get_contents($response->getFile()->getRealPath()), true))
        ->toBeArray();
});

test('a select records an audited query row', function (): void {
    $user = User::factory()->create();

    $service = new QueryService(new QueryRepository);

    $result = $service->executeSql([
        'sql' => 'SELECT id FROM products',
        'page' => 1,
        'page_size' => 20,
        'user_id' => $user->id,
        'need_insert_query' => true,
    ]);

    expect($result['status'])->toBeTrue()
        ->and(Query::count())->toBe(1)
        ->and(Query::first()->query_code)->not->toBeEmpty();
});
