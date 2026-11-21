<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Data;

use Illuminate\Contracts\Support\Arrayable;

readonly class CustomerCreateData implements Arrayable
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
        ];
    }
}
