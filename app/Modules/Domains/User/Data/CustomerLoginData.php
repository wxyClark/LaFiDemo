<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Data;

use Illuminate\Contracts\Support\Arrayable;

readonly class CustomerLoginData implements Arrayable
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            remember: $data['remember'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'remember' => $this->remember,
        ];
    }
}
