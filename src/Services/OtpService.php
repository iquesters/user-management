<?php

namespace Iquesters\UserManagement\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Contracts\OtpSender;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;
use Iquesters\UserManagement\Models\OtpAttempt;

class OtpService
{
    public function __construct(
        protected IdentifierResolverService $identifierResolverService,
        protected PhoneNumberService $phoneNumberService
    ) {
    }

    /**
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    public function sendLoginOtp(
        string $identifierType,
        string $identifierValue,
        string $deliveryChannel,
        ?string $requestIp = null,
        ?string $userAgent = null
    ): array {
        $config = $this->otpConfig();
        $normalizedIdentifier = $this->normalizeIdentifier($identifierType, $identifierValue);
        $maskedIdentifier = $this->identifierResolverService->maskIdentifier($identifierType, $normalizedIdentifier);
        $cooldownSeconds = (int) ($config->resend_cooldown_seconds ?? 60);
        $genericResponse = [
            'success' => true,
            'message' => $deliveryChannel === 'email'
                ? 'If this email address is registered, a verification code has been sent.'
                : 'If this WhatsApp number is registered, a verification code has been sent.',
            'cooldown_seconds' => $cooldownSeconds,
        ];

        Log::info('Processing OTP send request for login.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => 'send_otp',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $maskedIdentifier,
            'request_ip' => $requestIp,
        ]);

        if (!$this->isChannelEnabled($deliveryChannel)) {
            Log::warning('OTP send attempted while delivery channel is disabled.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => 'send_otp',
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
            ]);

            return [
                'success' => false,
                'message' => ucfirst($deliveryChannel) . ' login is currently unavailable.',
                'cooldown_seconds' => $cooldownSeconds,
            ];
        }

        if ($normalizedIdentifier === '') {
            Log::warning('OTP send request rejected because identifier normalization failed.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => 'send_otp',
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'request_ip' => $requestIp,
            ]);

            return $genericResponse;
        }

        $user = $this->identifierResolverService->findUserByIdentifier($identifierType, $normalizedIdentifier);

        Log::info('Resolved login OTP user lookup.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => 'send_otp',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $maskedIdentifier,
            'user_found' => $user !== null,
            'user_id' => $user?->id,
        ]);

        if (!$user) {
            return $genericResponse;
        }

        return $this->dispatchOtp(
            identifierType: $identifierType,
            identifierValue: $normalizedIdentifier,
            deliveryChannel: $deliveryChannel,
            requestIp: $requestIp,
            userAgent: $userAgent,
            authOperation: 'send_otp',
            successMessage: $genericResponse['message'],
            failureMessage: 'Unable to send the verification code right now.',
            genericResponse: $genericResponse,
            user: $user
        );
    }

    /**
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    public function resendLoginOtp(
        string $identifierType,
        string $identifierValue,
        string $deliveryChannel,
        ?string $requestIp = null,
        ?string $userAgent = null
    ): array {
        Log::info('Processing OTP resend request for login.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => 'resend_otp',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $this->identifierResolverService->maskIdentifier($identifierType, $identifierValue),
            'request_ip' => $requestIp,
        ]);

        return $this->sendLoginOtp($identifierType, $identifierValue, $deliveryChannel, $requestIp, $userAgent);
    }

    /**
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    public function sendRegistrationOtp(
        string $identifierType,
        string $identifierValue,
        string $deliveryChannel,
        ?string $requestIp = null,
        ?string $userAgent = null
    ): array {
        $config = $this->otpConfig();
        $cooldownSeconds = (int) ($config->resend_cooldown_seconds ?? 60);
        $normalizedIdentifier = $this->normalizeIdentifier($identifierType, $identifierValue);
        $maskedIdentifier = $this->identifierResolverService->maskIdentifier($identifierType, $normalizedIdentifier);
        $genericResponse = [
            'success' => true,
            'message' => $deliveryChannel === 'email'
                ? 'If verification can continue for this email address, a verification code has been sent.'
                : 'If verification can continue for this WhatsApp number, a verification code has been sent.',
            'cooldown_seconds' => $cooldownSeconds,
        ];

        Log::info('Processing OTP send request for registration.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => 'send_register_otp',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $maskedIdentifier,
            'request_ip' => $requestIp,
        ]);

        if (!$this->isChannelEnabled($deliveryChannel)) {
            return [
                'success' => false,
                'message' => ucfirst($deliveryChannel) . ' registration is currently unavailable.',
                'cooldown_seconds' => $cooldownSeconds,
            ];
        }

        if ($normalizedIdentifier === '') {
            return $genericResponse;
        }

        return $this->dispatchOtp(
            identifierType: $identifierType,
            identifierValue: $normalizedIdentifier,
            deliveryChannel: $deliveryChannel,
            requestIp: $requestIp,
            userAgent: $userAgent,
            authOperation: 'send_register_otp',
            successMessage: $genericResponse['message'],
            failureMessage: 'Unable to send the verification code right now.',
            genericResponse: $genericResponse,
            user: $this->identifierResolverService->findUserByIdentifier($identifierType, $normalizedIdentifier)
        );
    }

    /**
     * @return array{success:bool,message:string,user:?User}
     */
    public function verifyLoginOtp(
        string $identifierType,
        string $identifierValue,
        string $deliveryChannel,
        string $otp,
        ?string $requestIp = null
    ): array {
        $challengeResult = $this->verifyOtpChallenge($identifierType, $identifierValue, $deliveryChannel, $otp, $requestIp);

        if (!$challengeResult['success']) {
            return [
                'success' => false,
                'message' => $challengeResult['message'],
                'user' => null,
            ];
        }

        $user = $this->identifierResolverService->findUserByIdentifier($identifierType, $challengeResult['normalized_identifier']);

        if (!$user) {
            Log::error('OTP verification succeeded but no matching user was found for login.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => 'verify_otp',
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $challengeResult['masked_identifier'],
                'otp_attempt_id' => $challengeResult['otp_attempt']?->id,
            ]);

            $this->hitVerificationLimiter($deliveryChannel, $requestIp);

            return [
                'success' => false,
                'message' => 'Unable to complete login for this identifier.',
                'user' => null,
            ];
        }

        if ($identifierType === 'phone' && empty($user->phone_verified_at)) {
            $user->forceFill([
                'phone_verified_at' => Carbon::now(),
            ])->save();
        }

        if ($identifierType === 'email' && empty($user->email_verified_at)) {
            $user->markEmailAsVerified();
        }

        Log::info('OTP verification succeeded for login.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => 'verify_otp',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $challengeResult['masked_identifier'],
            'otp_attempt_id' => $challengeResult['otp_attempt']?->id,
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'message' => 'Verification successful.',
            'user' => $user,
        ];
    }

    /**
     * @return array{success:bool,message:string,otp_attempt:?OtpAttempt,normalized_identifier:?string,masked_identifier:?string}
     */
    public function verifyOtpChallenge(
        string $identifierType,
        string $identifierValue,
        string $deliveryChannel,
        string $otp,
        ?string $requestIp = null
    ): array {
        $config = $this->otpConfig();
        $normalizedIdentifier = $this->normalizeIdentifier($identifierType, $identifierValue);
        $maskedIdentifier = $this->identifierResolverService->maskIdentifier($identifierType, $normalizedIdentifier);

        Log::info('Processing OTP verification request.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => 'verify_otp',
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $maskedIdentifier,
            'request_ip' => $requestIp,
        ]);

        if (!$this->isChannelEnabled($deliveryChannel)) {
            return [
                'success' => false,
                'message' => ucfirst($deliveryChannel) . ' login is currently unavailable.',
                'otp_attempt' => null,
                'normalized_identifier' => null,
                'masked_identifier' => $maskedIdentifier,
            ];
        }

        if ($normalizedIdentifier === '') {
            return [
                'success' => false,
                'message' => 'The verification code is invalid or expired.',
                'otp_attempt' => null,
                'normalized_identifier' => null,
                'masked_identifier' => $maskedIdentifier,
            ];
        }

        if ($requestIp && RateLimiter::tooManyAttempts($this->verificationLimiterKey($deliveryChannel, $requestIp), (int) $config->max_verify_failures_per_window)) {
            Log::warning('OTP verification blocked by IP rate limit.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => 'verify_otp',
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'request_ip' => $requestIp,
            ]);

            return [
                'success' => false,
                'message' => 'Too many verification attempts. Please wait and try again.',
                'otp_attempt' => null,
                'normalized_identifier' => $normalizedIdentifier,
                'masked_identifier' => $maskedIdentifier,
            ];
        }

        return DB::transaction(function () use ($identifierType, $normalizedIdentifier, $deliveryChannel, $otp, $requestIp, $maskedIdentifier) {
            $otpAttempt = OtpAttempt::query()
                ->where('identifier_type', $identifierType)
                ->where('identifier_value', $normalizedIdentifier)
                ->where('delivery_channel', $deliveryChannel)
                ->where('status', 'active')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$otpAttempt) {
                Log::warning('OTP verification failed because no active attempt was found.', [
                    'auth_method' => $deliveryChannel . '_otp',
                    'operation' => 'verify_otp',
                    'identifier_type' => $identifierType,
                    'delivery_channel' => $deliveryChannel,
                    'masked_identifier' => $maskedIdentifier,
                ]);

                $this->hitVerificationLimiter($deliveryChannel, $requestIp);

                return [
                    'success' => false,
                    'message' => 'The verification code is invalid or expired.',
                    'otp_attempt' => null,
                    'normalized_identifier' => $normalizedIdentifier,
                    'masked_identifier' => $maskedIdentifier,
                ];
            }

            if ($otpAttempt->expires_at && $otpAttempt->expires_at->isPast()) {
                $otpAttempt->forceFill(['status' => 'expired'])->save();

                Log::info('OTP verification rejected because the code expired.', [
                    'auth_method' => $deliveryChannel . '_otp',
                    'operation' => 'verify_otp',
                    'identifier_type' => $identifierType,
                    'delivery_channel' => $deliveryChannel,
                    'masked_identifier' => $maskedIdentifier,
                    'otp_attempt_id' => $otpAttempt->id,
                ]);

                $this->hitVerificationLimiter($deliveryChannel, $requestIp);

                return [
                    'success' => false,
                    'message' => 'The verification code is invalid or expired.',
                    'otp_attempt' => null,
                    'normalized_identifier' => $normalizedIdentifier,
                    'masked_identifier' => $maskedIdentifier,
                ];
            }

            if ($otpAttempt->consumed_at !== null) {
                Log::warning('OTP verification rejected because the code was already consumed.', [
                    'auth_method' => $deliveryChannel . '_otp',
                    'operation' => 'verify_otp',
                    'identifier_type' => $identifierType,
                    'delivery_channel' => $deliveryChannel,
                    'masked_identifier' => $maskedIdentifier,
                    'otp_attempt_id' => $otpAttempt->id,
                ]);

                $this->hitVerificationLimiter($deliveryChannel, $requestIp);

                return [
                    'success' => false,
                    'message' => 'The verification code is invalid or expired.',
                    'otp_attempt' => null,
                    'normalized_identifier' => $normalizedIdentifier,
                    'masked_identifier' => $maskedIdentifier,
                ];
            }

            if ($otpAttempt->attempt_count >= $otpAttempt->max_attempts) {
                $otpAttempt->forceFill(['status' => 'locked'])->save();

                Log::warning('OTP verification rejected because max attempts were exceeded.', [
                    'auth_method' => $deliveryChannel . '_otp',
                    'operation' => 'verify_otp',
                    'identifier_type' => $identifierType,
                    'delivery_channel' => $deliveryChannel,
                    'masked_identifier' => $maskedIdentifier,
                    'otp_attempt_id' => $otpAttempt->id,
                ]);

                $this->hitVerificationLimiter($deliveryChannel, $requestIp);

                return [
                    'success' => false,
                    'message' => 'The verification code is invalid or expired.',
                    'otp_attempt' => null,
                    'normalized_identifier' => $normalizedIdentifier,
                    'masked_identifier' => $maskedIdentifier,
                ];
            }

            if (!Hash::check($otp, $otpAttempt->code_hash)) {
                $attemptCount = $otpAttempt->attempt_count + 1;
                $otpAttempt->forceFill(['attempt_count' => $attemptCount])->save();
                $this->hitVerificationLimiter($deliveryChannel, $requestIp);

                Log::warning('OTP verification rejected because the provided code did not match.', [
                    'auth_method' => $deliveryChannel . '_otp',
                    'operation' => 'verify_otp',
                    'identifier_type' => $identifierType,
                    'delivery_channel' => $deliveryChannel,
                    'masked_identifier' => $maskedIdentifier,
                    'otp_attempt_id' => $otpAttempt->id,
                    'attempt_count' => $attemptCount,
                ]);

                return [
                    'success' => false,
                    'message' => 'The verification code is invalid or expired.',
                    'otp_attempt' => null,
                    'normalized_identifier' => $normalizedIdentifier,
                    'masked_identifier' => $maskedIdentifier,
                ];
            }

            $otpAttempt->forceFill([
                'attempt_count' => $otpAttempt->attempt_count + 1,
                'status' => 'consumed',
                'consumed_at' => now(),
            ])->save();

            Log::info('OTP verification challenge succeeded.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => 'verify_otp',
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'otp_attempt_id' => $otpAttempt->id,
            ]);

            return [
                'success' => true,
                'message' => 'Verification successful.',
                'otp_attempt' => $otpAttempt,
                'normalized_identifier' => $normalizedIdentifier,
                'masked_identifier' => $maskedIdentifier,
            ];
        });
    }

    public function findUserByPhone(string $phone): ?User
    {
        return $this->identifierResolverService->findUserByIdentifier('phone', $phone);
    }

    protected function normalizeIdentifier(string $identifierType, string $identifierValue): string
    {
        return $identifierType === 'email'
            ? $this->identifierResolverService->normalizeEmail($identifierValue)
            : $this->identifierResolverService->normalizePhone($identifierValue);
    }

    /**
     * @param array{success:bool,message:string,cooldown_seconds:int} $genericResponse
     * @return array{success:bool,message:string,cooldown_seconds:int}
     */
    protected function dispatchOtp(
        string $identifierType,
        string $identifierValue,
        string $deliveryChannel,
        ?string $requestIp,
        ?string $userAgent,
        string $authOperation,
        string $successMessage,
        string $failureMessage,
        array $genericResponse,
        ?User $user = null
    ): array {
        $config = $this->otpConfig();
        $maskedIdentifier = $this->identifierResolverService->maskIdentifier($identifierType, $identifierValue);
        $cooldownSeconds = (int) ($config->resend_cooldown_seconds ?? 60);

        if ($this->isPerIdentifierRateLimited($deliveryChannel, $identifierType, $identifierValue, (int) $config->max_send_per_hour)) {
            Log::warning('OTP send blocked by per-identifier rate limit.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => $authOperation,
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'user_id' => $user?->id,
            ]);

            return $genericResponse;
        }

        if ($requestIp && $this->isPerIpRateLimited($deliveryChannel, $requestIp, (int) $config->max_send_per_hour)) {
            Log::warning('OTP send blocked by per-IP rate limit.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => $authOperation,
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'user_id' => $user?->id,
                'request_ip' => $requestIp,
            ]);

            return $genericResponse;
        }

        if ($this->isGlobalRateLimited($deliveryChannel, (int) $config->max_global_sends_per_hour)) {
            Log::warning('OTP send blocked by global send cap.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => $authOperation,
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'user_id' => $user?->id,
            ]);

            return $genericResponse;
        }

        $latestActiveAttempt = OtpAttempt::query()
            ->where('identifier_type', $identifierType)
            ->where('identifier_value', $identifierValue)
            ->where('delivery_channel', $deliveryChannel)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if ($latestActiveAttempt && $latestActiveAttempt->last_sent_at?->gt(now()->subSeconds($cooldownSeconds))) {
            Log::info('OTP send suppressed by resend cooldown.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => $authOperation,
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'user_id' => $user?->id,
                'otp_attempt_id' => $latestActiveAttempt->id,
            ]);

            return [
                'success' => true,
                'message' => $successMessage,
                'cooldown_seconds' => $cooldownSeconds,
            ];
        }

        OtpAttempt::query()
            ->where('identifier_type', $identifierType)
            ->where('identifier_value', $identifierValue)
            ->where('delivery_channel', $deliveryChannel)
            ->where('status', 'active')
            ->update([
                'status' => 'superseded',
            ]);

        $otp = $this->generateOtp((int) $config->otp_length);
        $now = now();
        $otpAttempt = OtpAttempt::create([
            'identifier_type' => $identifierType,
            'identifier_value' => $identifierValue,
            'delivery_channel' => $deliveryChannel,
            'code_hash' => Hash::make($otp),
            'expires_at' => $now->copy()->addMinutes((int) $config->otp_ttl_minutes),
            'attempt_count' => 0,
            'max_attempts' => (int) $config->max_attempts,
            'last_sent_at' => $now,
            'status' => 'active',
        ]);

        $otpAttempt->setMetaValue('request_ip', (string) $requestIp);
        $otpAttempt->setMetaValue('user_agent', substr((string) $userAgent, 0, 1000));
        $otpAttempt->setMetaValue('delivery_status', 'queued');
        $otpAttempt->setMetaValue('delivery_status_updated_at', $now->toDateTimeString());

        Log::info('Created OTP attempt.', [
            'auth_method' => $deliveryChannel . '_otp',
            'operation' => $authOperation,
            'identifier_type' => $identifierType,
            'delivery_channel' => $deliveryChannel,
            'masked_identifier' => $maskedIdentifier,
            'user_id' => $user?->id,
            'otp_attempt_id' => $otpAttempt->id,
        ]);

        try {
            $deliveryResult = $this->resolveSender($deliveryChannel)->sendOtp($identifierValue, $otp, [
                'otp_attempt_id' => $otpAttempt->id,
                'user_id' => $user?->id,
                'delivery_channel' => $deliveryChannel,
                'identifier_type' => $identifierType,
                'template_name' => $this->otpConfig()->verify_template_name ?? 'login_verification',
            ]);

            $otpAttempt->setMetaValue('provider_message_id', (string) ($deliveryResult['provider_message_id'] ?? ''));
            $otpAttempt->setMetaValue('delivery_status', (string) ($deliveryResult['delivery_status'] ?? 'sent'));
            $otpAttempt->setMetaValue('delivery_status_updated_at', now()->toDateTimeString());

            $this->hitSendRateLimiters($deliveryChannel, $identifierType, $identifierValue, $requestIp);

            Log::info('OTP delivery dispatched successfully.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => $authOperation,
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'user_id' => $user?->id,
                'otp_attempt_id' => $otpAttempt->id,
                'provider_message_id' => $otpAttempt->getMetaValue('provider_message_id'),
                'delivery_status' => $otpAttempt->getMetaValue('delivery_status'),
            ]);
        } catch (\Throwable $throwable) {
            $otpAttempt->forceFill(['status' => 'failed'])->save();
            $otpAttempt->setMetaValue('delivery_status', 'failed');
            $otpAttempt->setMetaValue('delivery_status_updated_at', now()->toDateTimeString());

            Log::error('OTP delivery failed.', [
                'auth_method' => $deliveryChannel . '_otp',
                'operation' => $authOperation,
                'identifier_type' => $identifierType,
                'delivery_channel' => $deliveryChannel,
                'masked_identifier' => $maskedIdentifier,
                'user_id' => $user?->id,
                'otp_attempt_id' => $otpAttempt->id,
                'error' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $failureMessage,
                'cooldown_seconds' => $cooldownSeconds,
            ];
        }

        return [
            'success' => true,
            'message' => $successMessage,
            'cooldown_seconds' => $cooldownSeconds,
        ];
    }

    protected function resolveSender(string $deliveryChannel): OtpSender
    {
        return match ($deliveryChannel) {
            'whatsapp' => app(WhatsAppOtpSender::class),
            'email' => app(EmailOtpSender::class),
            default => throw new \InvalidArgumentException('Unsupported OTP delivery channel: ' . $deliveryChannel),
        };
    }

    protected function otpConfig()
    {
        return ConfProvider::from(Module::USER_MGMT)->whatsapp_login;
    }

    protected function isChannelEnabled(string $deliveryChannel): bool
    {
        if ($deliveryChannel === 'email') {
            return true;
        }

        return (bool) ($this->otpConfig()->enabled ?? false);
    }

    protected function generateOtp(int $length): string
    {
        $maxDigits = max($length, 4);
        $min = 10 ** ($maxDigits - 1);
        $max = (10 ** $maxDigits) - 1;

        return (string) random_int($min, $max);
    }

    protected function isPerIdentifierRateLimited(string $deliveryChannel, string $identifierType, string $identifierValue, int $maxAttempts): bool
    {
        return RateLimiter::tooManyAttempts($this->perIdentifierLimiterKey($deliveryChannel, $identifierType, $identifierValue), $maxAttempts);
    }

    protected function isPerIpRateLimited(string $deliveryChannel, string $requestIp, int $maxAttempts): bool
    {
        return RateLimiter::tooManyAttempts($this->perIpLimiterKey($deliveryChannel, $requestIp), $maxAttempts);
    }

    protected function isGlobalRateLimited(string $deliveryChannel, int $maxAttempts): bool
    {
        return RateLimiter::tooManyAttempts($this->globalLimiterKey($deliveryChannel), $maxAttempts);
    }

    protected function hitSendRateLimiters(string $deliveryChannel, string $identifierType, string $identifierValue, ?string $requestIp): void
    {
        $decaySeconds = 3600;
        RateLimiter::hit($this->perIdentifierLimiterKey($deliveryChannel, $identifierType, $identifierValue), $decaySeconds);
        RateLimiter::hit($this->globalLimiterKey($deliveryChannel), $decaySeconds);

        if ($requestIp) {
            RateLimiter::hit($this->perIpLimiterKey($deliveryChannel, $requestIp), $decaySeconds);
        }
    }

    protected function hitVerificationLimiter(string $deliveryChannel, ?string $requestIp): void
    {
        if ($requestIp) {
            RateLimiter::hit($this->verificationLimiterKey($deliveryChannel, $requestIp), 3600);
        }
    }

    protected function perIdentifierLimiterKey(string $deliveryChannel, string $identifierType, string $identifierValue): string
    {
        $identifierKey = $identifierType === 'phone'
            ? ltrim($this->phoneNumberService->normalize($identifierValue), '+')
            : strtolower(trim($identifierValue));

        return 'user-management:' . $deliveryChannel . '-otp:send:' . $identifierType . ':' . $identifierKey;
    }

    protected function perIpLimiterKey(string $deliveryChannel, string $requestIp): string
    {
        return 'user-management:' . $deliveryChannel . '-otp:send:ip:' . $requestIp;
    }

    protected function globalLimiterKey(string $deliveryChannel): string
    {
        return 'user-management:' . $deliveryChannel . '-otp:send:global';
    }

    protected function verificationLimiterKey(string $deliveryChannel, string $requestIp): string
    {
        return 'user-management:' . $deliveryChannel . '-otp:verify:ip:' . $requestIp;
    }
}
