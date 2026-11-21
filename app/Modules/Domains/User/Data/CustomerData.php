<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Data;

use App\Modules\Domains\User\Models\Customer;
use Illuminate\Contracts\Support\Arrayable;

readonly class CustomerData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $emailVerifiedAt,
    ) {}

    public static function fromModel(Customer $customer): self
    {
        return new self(
            id: $customer->id,
            name: $customer->name,
            email: $customer->email,
            phone: $customer->phone,
            emailVerifiedAt: $customer->email_verified_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->emailVerifiedAt,
        ];
    }
}
