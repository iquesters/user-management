<?php

namespace Iquesters\UserManagement\Http\Requests\Auth\Concerns;

use Illuminate\Validation\Validator;
use Iquesters\UserManagement\Services\PhoneNumberService;

trait ValidatesNormalizedPhoneNumber
{
    protected function validateNormalizedPhoneNumber(Validator $validator, string $field = 'phone'): void
    {
        $validator->after(function (Validator $validator) use ($field) {
            $rawPhone = (string) $this->input($field, '');
            $normalizedPhone = app(PhoneNumberService::class)->normalize($rawPhone);
            $digitsOnly = ltrim($normalizedPhone, '+');

            if ($normalizedPhone === '' || !preg_match('/^\d{7,15}$/', $digitsOnly)) {
                $validator->errors()->add($field, 'Please provide a valid phone number including country code when needed.');
            }
        });
    }
}
