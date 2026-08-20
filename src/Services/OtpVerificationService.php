<?php

namespace Iquesters\UserManagement\Services;

use App\Models\User;
use Iquesters\UserManagement\Models\OtpAttempt;

class OtpVerificationService
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    /**
     * @return array{success:bool,message:string,user:?User}
     */
    public function verifyOtp(string $phone, string $otp, ?string $requestIp = null): array
    {
        return $this->otpService->verifyLoginOtp('phone', $phone, 'whatsapp', $otp, $requestIp);
    }

    /**
     * @return array{success:bool,message:string,otp_attempt:?OtpAttempt,normalized_phone:?string,masked_phone:?string}
     */
    public function verifyPhoneOtpChallenge(string $phone, string $otp, ?string $requestIp = null): array
    {
        $result = $this->otpService->verifyOtpChallenge('phone', $phone, 'whatsapp', $otp, $requestIp);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'otp_attempt' => $result['otp_attempt'],
            'normalized_phone' => $result['normalized_identifier'],
            'masked_phone' => $result['masked_identifier'],
        ];
    }
}
