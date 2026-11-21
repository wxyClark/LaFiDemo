<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Data;

use Illuminate\Contracts\Support\Arrayable;

readonly class CustomerUpdateData implements Arrayable
{
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
        ], fn ($v) => $v !== null);
    }
}
