<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Http\Requests;

use App\Modules\Domains\User\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:'.(new Customer)->getTable().',email'],
            'phone' => ['nullable', 'string', 'max:20'],
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
