<?php

namespace Iquesters\UserManagement\Contracts;

interface OtpSender
{
    /**
     * @return array<string, mixed>
     */
    public function sendOtp(string $identifier, string $otp, array $context = []): array;
}
