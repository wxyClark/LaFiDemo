<?php

declare(strict_types=1);

namespace App\Modules\Domains\Logging\Facades;

use App\Modules\Domains\Logging\Services\LogService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Modules\Domains\Logging\Models\BusinessLog info(string $message, array $context = [])
 * @method static \App\Modules\Domains\Logging\Models\BusinessLog warning(string $message, array $context = [])
 * @method static \App\Modules\Domains\Logging\Models\BusinessLog error(string $message, array $context = [])
 * @method static \App\Modules\Domains\Logging\Models\BusinessLog critical(string $message, array $context = [])
 * @method static \App\Modules\Domains\Logging\Models\BusinessLog debug(string $message, array $context = [])
 * @method static array getLogsByRequestId(string $requestId)
 * @method static array getStats(int $hours = 24)
 */
class Log extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LogService::class;
    }
}
