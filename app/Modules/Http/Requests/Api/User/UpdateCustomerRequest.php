<?php

declare(strict_types=1);

namespace App\Modules\Http\Requests\Api\User;

use App\Modules\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:'.(new Customer)->getTable().',email,'.$customerId],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => '邮箱已被使用',
            'password.confirmed' => '两次输入的密码不一致',
        ];
    }
}
