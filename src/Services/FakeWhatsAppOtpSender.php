<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;

class FakeWhatsAppOtpSender implements WhatsAppOtpSender
{
    public function __construct(
        protected PhoneNumberService $phoneNumberService
    ) {
    }

    public function sendOtp(string $phone, string $otp, array $context = []): array
    {
        $providerMessageId = 'fake-wa-' . Str::uuid()->toString();
        $maskedPhone = $this->phoneNumberService->mask($phone);

        Log::info('Simulated WhatsApp OTP delivery queued.', [
            'auth_method' => 'whatsapp_otp',
            'operation' => 'send_otp',
            'provider' => 'fake',
            'masked_phone' => $maskedPhone,
            'provider_message_id' => $providerMessageId,
            'context_keys' => array_keys($context),
        ]);

        return [
            'provider_message_id' => $providerMessageId,
            'delivery_status' => 'sent',
        ];
    }
}
