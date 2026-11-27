<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;

class VerifyIdentifierOtpRequest extends FormRequest
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
            // Exactly the code sent: its length (setting otp_length), digits only.
            'otp' => ['required', 'digits:'.self::otpLength()],
        ];
    }

    public function messages(): array
    {
        return ['otp.digits' => 'Enter the '.self::otpLength().'-digit code we sent you.'];
    }

    /** How many digits a code has (the OTP setting, 6 by default). */
    public static function otpLength(): int
    {
        try {
            $length = (int) ConfProvider::from(Module::USER_MGMT)->whatsapp_login->otp_length;
        } catch (\Throwable $e) {
            $length = 0;
        }

        return $length >= 4 && $length <= 10 ? $length : 6;
    }
}
