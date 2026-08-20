<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Iquesters\UserManagement\Http\Requests\Auth\Concerns\ValidatesNormalizedPhoneNumber;

class SendWhatsappOtpRequest extends FormRequest
{
    use ValidatesNormalizedPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:7', 'max:32'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->validateNormalizedPhoneNumber($validator);
    }
}
