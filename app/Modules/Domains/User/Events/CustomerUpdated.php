<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Events;

use App\Modules\Domains\User\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Customer $customer
    ) {}
}
