<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $customerId
    ) {}
}
