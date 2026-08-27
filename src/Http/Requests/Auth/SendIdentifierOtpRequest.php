<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendIdentifierOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier_type' => ['required', Rule::in(['email', 'phone'])],
            'identifier' => ['required', 'string', 'min:3', 'max:255'],
            'delivery_channel' => ['required', Rule::in(['email', 'whatsapp'])],
            'flow_token' => ['required', 'string', 'min:20', 'max:128'],
        ];
    }
}
