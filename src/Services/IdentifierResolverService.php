<?php

namespace Iquesters\UserManagement\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class IdentifierResolverService
{
    public function __construct(
        protected PhoneNumberService $phoneNumberService
    ) {
    }

    /**
     * @return array{identifier_type:string,normalized_identifier:string,masked_identifier:string,delivery_channel:string,user:?User}
     */
    public function resolve(string $identifier, ?string $countryDialCode = null): array
    {
        $trimmedIdentifier = trim($identifier);
        $identifierType = filter_var($trimmedIdentifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $normalizedIdentifier = $identifierType === 'email'
            ? $this->normalizeEmail($trimmedIdentifier)
            : $this->normalizePhone($trimmedIdentifier, $countryDialCode);
        $deliveryChannel = $identifierType === 'email' ? 'email' : 'whatsapp';
        $user = $this->findUserByIdentifier($identifierType, $normalizedIdentifier);

        Log::info('Resolved auth identifier.', [
            'auth_method' => 'unified_auth',
            'operation' => 'resolve_identifier',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $this->maskIdentifier($identifierType, $normalizedIdentifier),
            'user_found' => $user !== null,
            'user_id' => $user?->id,
        ]);

        return [
            'identifier_type' => $identifierType,
            'normalized_identifier' => $normalizedIdentifier,
            'masked_identifier' => $this->maskIdentifier($identifierType, $normalizedIdentifier),
            'delivery_channel' => $deliveryChannel,
            'user' => $user,
        ];
    }

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function normalizePhone(string $phone, ?string $countryDialCode = null): string
    {
        $normalizedPhone = $this->phoneNumberService->normalize($phone);

        if ($normalizedPhone === '') {
            return '';
        }

        if (str_starts_with($normalizedPhone, '+')) {
            return $normalizedPhone;
        }

        $normalizedDialCode = $this->phoneNumberService->normalize((string) $countryDialCode);

        if ($normalizedDialCode !== '') {
            return '+' . ltrim($normalizedDialCode, '+') . ltrim($normalizedPhone, '+');
        }

        return $normalizedPhone;
    }

    public function maskIdentifier(string $identifierType, string $identifier): string
    {
        if ($identifierType === 'email') {
            $normalizedEmail = $this->normalizeEmail($identifier);

            if (!str_contains($normalizedEmail, '@')) {
                return '***';
            }

            [$localPart, $domain] = explode('@', $normalizedEmail, 2);
            $visible = substr($localPart, 0, 2);

            return $visible . str_repeat('*', max(strlen($localPart) - 2, 1)) . '@' . $domain;
        }

        return $this->phoneNumberService->mask($identifier);
    }

    public function findUserByIdentifier(string $identifierType, string $identifierValue): ?User
    {
        if ($identifierValue === '') {
            return null;
        }

        $query = User::query()->where(function ($statusQuery) {
            $statusQuery->whereNull('status')->orWhere('status', 'active');
        });

        if ($identifierType === 'email') {
            return $query->where('email', $this->normalizeEmail($identifierValue))->first();
        }

        if ($identifierType === 'phone') {
            return $query->whereIn('phone', $this->phoneNumberService->buildLookupCandidates($identifierValue))->first();
        }

        return null;
    }

    /**
     * @return array<int, array{country_code:string,dial_code:string,label:string}>
     */
    public function getCountryDialCodes(): array
    {
        return [
            ['country_code' => 'US', 'dial_code' => '+1', 'label' => 'United States (+1)'],
            ['country_code' => 'CA', 'dial_code' => '+1', 'label' => 'Canada (+1)'],
            ['country_code' => 'GB', 'dial_code' => '+44', 'label' => 'United Kingdom (+44)'],
            ['country_code' => 'IN', 'dial_code' => '+91', 'label' => 'India (+91)'],
            ['country_code' => 'AU', 'dial_code' => '+61', 'label' => 'Australia (+61)'],
            ['country_code' => 'AE', 'dial_code' => '+971', 'label' => 'United Arab Emirates (+971)'],
            ['country_code' => 'BD', 'dial_code' => '+880', 'label' => 'Bangladesh (+880)'],
            ['country_code' => 'DE', 'dial_code' => '+49', 'label' => 'Germany (+49)'],
            ['country_code' => 'FR', 'dial_code' => '+33', 'label' => 'France (+33)'],
            ['country_code' => 'JP', 'dial_code' => '+81', 'label' => 'Japan (+81)'],
            ['country_code' => 'KE', 'dial_code' => '+254', 'label' => 'Kenya (+254)'],
            ['country_code' => 'NG', 'dial_code' => '+234', 'label' => 'Nigeria (+234)'],
            ['country_code' => 'PK', 'dial_code' => '+92', 'label' => 'Pakistan (+92)'],
            ['country_code' => 'SG', 'dial_code' => '+65', 'label' => 'Singapore (+65)'],
            ['country_code' => 'ZA', 'dial_code' => '+27', 'label' => 'South Africa (+27)'],
        ];
    }

    public function getDialCodeForCountry(?string $countryCode): string
    {
        $normalizedCountryCode = strtoupper(trim((string) $countryCode));

        foreach ($this->getCountryDialCodes() as $country) {
            if ($country['country_code'] === $normalizedCountryCode) {
                return $country['dial_code'];
            }
        }

        return '+1';
    }
}
