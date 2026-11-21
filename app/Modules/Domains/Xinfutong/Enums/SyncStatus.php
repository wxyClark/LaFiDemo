<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Enums;

enum SyncStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '等待中',
            self::SUCCESS => '成功',
            self::FAILED => '失败',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
        };
    }
}
