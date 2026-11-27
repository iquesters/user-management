<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Support\Facades\Log;
use Iquesters\UserManagement\Contracts\OtpSender;
use Iquesters\UserManagement\Support\SignInCodeEmail;

class EmailOtpSender implements OtpSender
{
    /**
     * @return array<string, mixed>
     */
    public function sendOtp(string $identifier, string $otp, array $context = []): array
    {
        $resolver = app(IdentifierResolverService::class);

        Log::info('Dispatching email OTP message.', [
            'auth_method' => 'email_otp',
            'operation' => 'send_otp',
            'masked_identifier' => $resolver->maskIdentifier('email', $identifier),
            'otp_attempt_id' => $context['otp_attempt_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
        ]);

        try {
            // The app's "Sign-in code" email (notifications template when installed, else built in).
            SignInCodeEmail::send($identifier, $otp, isset($context['user_id']) ? (int) $context['user_id'] : null);
        } catch (\Throwable $throwable) {
            Log::error('Email OTP delivery failed.', [
                'auth_method' => 'email_otp',
                'operation' => 'send_otp',
                'masked_identifier' => $resolver->maskIdentifier('email', $identifier),
                'otp_attempt_id' => $context['otp_attempt_id'] ?? null,
                'user_id' => $context['user_id'] ?? null,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        Log::info('Email OTP delivery dispatched successfully.', [
            'auth_method' => 'email_otp',
            'operation' => 'send_otp',
            'masked_identifier' => $resolver->maskIdentifier('email', $identifier),
            'otp_attempt_id' => $context['otp_attempt_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
        ]);

        return [
            'provider_message_id' => null,
            'delivery_status' => 'sent',
        ];
    }
}
