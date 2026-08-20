<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyAuthIdentifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'min:3', 'max:255'],
            'country_dial_code' => ['nullable', 'string', 'max:10'],
        ];
    }
}
