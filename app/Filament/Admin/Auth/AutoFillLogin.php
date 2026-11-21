<?php

declare(strict_types=1);

namespace App\Filament\Admin\Auth;

use Filament\Auth\Pages\Login;

class AutoFillLogin extends Login
{
    public function mount(): void
    {
        $email = env('FILAMENT_ADMIN_PREFILL_EMAIL');
        $password = env('FILAMENT_ADMIN_PREFILL_PASSWORD');

        if ($email !== null && $password !== null) {
            // Filament 5 keeps the form state in a nested array (`data` => [state]).
            $this->data = [[
                'email' => $email,
                'password' => $password,
                'remember' => true,
            ]];
        }

        parent::mount();
    }
}
