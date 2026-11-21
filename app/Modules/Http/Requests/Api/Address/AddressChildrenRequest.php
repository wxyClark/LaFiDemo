<?php

declare(strict_types=1);

namespace App\Modules\Http\Requests\Api\Address;

use Illuminate\Foundation\Http\FormRequest;

class AddressChildrenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:addresses,id'],
        ];
    }
}
