<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\SyncLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 同步日志仓储接口
 */
interface SyncRepositoryInterface
{
    public function findById(int $id): ?SyncLog;

    public function create(array $data): SyncLog;

    public function update(SyncLog $log, array $data): SyncLog;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getPending(): ?SyncLog;

    public function getRunning(): ?SyncLog;

    public function markSuccess(SyncLog $log, array $stats): SyncLog;

    public function markFailed(SyncLog $log, string $errorMessage): SyncLog;
}
