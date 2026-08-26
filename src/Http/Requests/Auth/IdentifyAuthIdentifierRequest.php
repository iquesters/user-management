<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Rules\RecaptchaRule;
use Iquesters\UserManagement\Support\AuthFormSchema;

class IdentifyAuthIdentifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Deliberately the phone schema, not the email one — its identifier
        // field has no 'email' type (so DynamicFormSchema::toRules() adds no
        // format rule), making it the permissive superset that accepts
        // submissions from either tab. Using the email schema here would
        // wrongly reject every phone submission with an 'email' format rule.
        $rules = AuthFormSchema::rules('unified-identify-phone') ?? [
            'identifier' => ['required', 'string', 'min:3', 'max:255'],
            'country_dial_code' => ['nullable', 'string', 'max:10'],
        ];

        // This is the first form of the unified flow — the entry point a bot
        // would hit to enumerate identifiers or force OTP sends, so it's the
        // one that most needs the gate (see LoginRequest for the same pattern
        // on the password path).
        if (ConfProvider::from(Module::USER_MGMT)->recaptcha->enabled ?? false) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('unified_identify', 0.5)];
        }

        return $rules;
    }
}
