<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Data;

use App\Modules\Models\Admin;
use Illuminate\Contracts\Support\Arrayable;

readonly class AuthResult implements Arrayable
{
    public function __construct(
        public Admin $admin,
        public string $token,
        public int $expiresIn,
    ) {}

    public function toArray(): array
    {
        return [
            'admin' => $this->admin,
            'token' => $this->token,
            'token_type' => 'bearer',
            'expires_in' => $this->expiresIn,
        ];
    }
}
