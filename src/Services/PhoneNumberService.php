<?php

namespace Iquesters\UserManagement\Services;

class PhoneNumberService
{
    public function normalize(string $phone): string
    {
        $trimmedPhone = trim($phone);

        if ($trimmedPhone === '') {
            return '';
        }

        $hasLeadingPlus = str_starts_with($trimmedPhone, '+');
        $digitsOnly = preg_replace('/\D+/', '', $trimmedPhone) ?? '';

        if ($digitsOnly === '') {
            return '';
        }

        return $hasLeadingPlus ? '+' . $digitsOnly : $digitsOnly;
    }

    /**
     * @return array<int, string>
     */
    public function buildLookupCandidates(string $phone): array
    {
        $normalizedPhone = $this->normalize($phone);

        if ($normalizedPhone === '') {
            return [];
        }

        $digitsOnly = ltrim($normalizedPhone, '+');

        return array_values(array_unique(array_filter([
            $normalizedPhone,
            $digitsOnly,
            '+' . $digitsOnly,
        ])));
    }

    public function mask(string $phone): string
    {
        $normalizedPhone = $this->normalize($phone);
        $digitsOnly = ltrim($normalizedPhone, '+');
        $lastFourDigits = substr($digitsOnly, -4);
        $maskedLength = max(strlen($digitsOnly) - 4, 0);
        $maskedDigits = str_repeat('*', $maskedLength) . $lastFourDigits;

        return str_starts_with($normalizedPhone, '+') ? '+' . $maskedDigits : $maskedDigits;
    }
}
