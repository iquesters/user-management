<?php

namespace Iquesters\UserManagement\Contracts;

interface WhatsAppOtpSender extends OtpSender
{
    /**
     * Send a one-time password to a WhatsApp number.
     *
     * @param string $phone
     * @param string $otp
     * @param array<string, mixed> $context
     * @return array{provider_message_id:string|null,delivery_status:string}
     */
    public function sendOtp(string $phone, string $otp, array $context = []): array;
}
