<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Iquesters\UserManagement\Contracts\OtpSender;

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
            Mail::raw(
                'Your verification code is ' . $otp . '. It will expire soon. If you did not request this code, you can ignore this email.',
                function ($message) use ($identifier) {
                    $message->to($identifier)->subject('Your verification code');
                }
            );
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
