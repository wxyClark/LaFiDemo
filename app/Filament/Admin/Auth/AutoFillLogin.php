<?php

declare(strict_types=1);

namespace App\Filament\Admin\Auth;

use Filament\Auth\Pages\Login;

class AutoFillLogin extends Login
{
    public function mount(): void
    {
        // Credentials never get prefilled in production.
        if (! app()->environment('production')) {
            $this->prefill();
        }

        parent::mount();
    }

    /**
     * Pre-fill the login form when developer credentials are configured.
     */
    protected function prefill(): void
    {
        $email = config('services.filament_admin.prefill_email');
        $password = config('services.filament_admin.prefill_password');

        if ($email !== null && $password !== null) {
            $this->data = [
                'email' => $email,
                'password' => $password,
                'remember' => true,
            ];
        }
    }
}
