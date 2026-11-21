<?php

declare(strict_types=1);

namespace App\Filament\Admin\Auth;

use Filament\Auth\Pages\Login;

class AutoFillLogin extends Login
{
    public function mount(): void
    {
        $email = config('services.filament_admin.prefill_email');
        $password = config('services.filament_admin.prefill_password');

        if ($email !== null && $password !== null) {
            // Prefill the login form before the parent mounts it.
            $this->data = [
                'email' => $email,
                'password' => $password,
                'remember' => true,
            ];
        }

        parent::mount();
    }
}
