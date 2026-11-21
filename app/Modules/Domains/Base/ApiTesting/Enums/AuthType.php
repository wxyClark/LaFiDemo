<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Enums;

enum AuthType: string
{
    case NONE = 'none';
    case JWT = 'jwt';
    case SESSION = 'session';
    case APIKEY = 'apikey';

    public function label(): string
    {
        return match ($this) {
            self::NONE => '无认证',
            self::JWT => 'JWT Token',
            self::SESSION => 'Session',
            self::APIKEY => 'API Key',
        };
    }
}
