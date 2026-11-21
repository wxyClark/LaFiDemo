<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Events;

use App\Modules\Models\Admin;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Admin $admin
    ) {}
}
