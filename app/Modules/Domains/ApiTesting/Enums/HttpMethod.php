<?php

declare(strict_types=1);

namespace App\Modules\Domains\ApiTesting\Enums;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';

    public function color(): string
    {
        return match ($this) {
            self::GET => 'success',
            self::POST => 'info',
            self::PUT => 'warning',
            self::PATCH => 'warning',
            self::DELETE => 'danger',
        };
    }
}
