<?php

namespace Iquesters\UserManagement\Services;

use App\Models\User;

class WhatsAppOtpService
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    /**
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    public function sendOtp(string $phone, ?string $requestIp = null, ?string $userAgent = null): array
    {
        return $this->otpService->sendLoginOtp('phone', $phone, 'whatsapp', $requestIp, $userAgent);
    }

    /**
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    public function resendOtp(string $phone, ?string $requestIp = null, ?string $userAgent = null): array
    {
        return $this->otpService->resendLoginOtp('phone', $phone, 'whatsapp', $requestIp, $userAgent);
    }

    /**
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    public function sendRegistrationOtp(string $phone, ?string $requestIp = null, ?string $userAgent = null): array
    {
        return $this->otpService->sendRegistrationOtp('phone', $phone, 'whatsapp', $requestIp, $userAgent);
    }

    public function findUserByPhone(string $phone): ?User
    {
        return $this->otpService->findUserByPhone($phone);
    }
}
