<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompleteIdentifierRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flow_token' => ['required', 'string', 'min:20', 'max:128'],
            'fields' => ['nullable', 'array'],
        ];
    }
}
