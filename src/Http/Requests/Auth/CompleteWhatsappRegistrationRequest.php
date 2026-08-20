<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWhatsappRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        ];
    }
}
