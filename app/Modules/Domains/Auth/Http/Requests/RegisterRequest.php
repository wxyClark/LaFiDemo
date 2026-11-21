<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Http\Requests;

use App\Modules\Domains\Auth\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:'.(new Admin)->getTable().',email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => '邮箱已注册',
            'password.confirmed' => '两次输入的密码不一致',
        ];
    }
}
