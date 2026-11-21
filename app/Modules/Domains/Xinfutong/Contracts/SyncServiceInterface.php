<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\SyncLog;

/**
 * 同步服务接口
 */
interface SyncServiceInterface
{
    public function syncEmployees(): SyncLog;

    public function syncIncremental(): SyncLog;

    public function getSyncStatus(): ?SyncLog;

    public function getLastSyncTime(): ?string;
}
