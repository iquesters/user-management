<?php

namespace Iquesters\UserManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;

/**
 * Customer order challenge, NOT login OTP.
 *
 * Identity is (organisation, store, provider order, requesting WhatsApp,
 * destination billing phone, receiving organisation channel). Codes never
 * leave this service. A successful verify cannot unlock another order.
 */
final class OrderOtpService
{
    public function __construct(
        private readonly PhoneNumberService $phones,
        private readonly WhatsAppOtpSender $sender,
    ) {}

    /** @return array{accepted:bool,challenge_id:?string} */
    public function issue(
        int $organisationId, string $channelUid, string $chatbotUid,
        int $storeId, string $orderId, string $requesterPhone, string $orderPhone
    ): array {
        $destination = $this->phone($orderPhone);
        $requester = $this->phone($requesterPhone);
        $scope = $this->scope($organisationId, $channelUid, $chatbotUid, $storeId, $orderId, $requester, $destination);
        if ($scope === '' || $destination === '' || $requester === '' || $destination === $requester) {
            // Matching order phone does not need an OTP. It is the caller's
            // responsibility to verify the order ownership first.
            return ['accepted' => false, 'challenge_id' => null];
        }

        $identifier = hash('sha256', $organisationId.'|'.$destination);
        $limiter = 'order-otp-send:'.$identifier;
        $requesterLimiter = 'order-otp-requester:'.hash('sha256', $organisationId.'|'.$requester);
        $orgLimiter = 'order-otp-organisation:'.$organisationId;
        if (RateLimiter::tooManyAttempts($limiter, 3)
            || RateLimiter::tooManyAttempts($requesterLimiter, 5)
            || RateLimiter::tooManyAttempts($orgLimiter, 250)) {
            Log::warning('order_otp.issue_rate_limited', ['organisation_id' => $organisationId]);
            return ['accepted' => false, 'challenge_id' => null];
        }

        $previous = DB::table('order_otp_challenges')
            ->where('organisation_id', $organisationId)
            ->where('scope_hash', $scope)
            ->where('status', 'active')
            ->latest('created_at')->first();
        if ($previous && Carbon::parse($previous->created_at)->greaterThan(now()->subSeconds(60))) {
            return ['accepted' => true, 'challenge_id' => (string) $previous->id];
        }

        DB::table('order_otp_challenges')
            ->where('organisation_id', $organisationId)
            ->where('scope_hash', $scope)
            ->where('status', 'active')->update(['status' => 'superseded', 'updated_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $id = (string) Str::uuid();
        DB::table('order_otp_challenges')->insert([
            'id' => $id,
            'organisation_id' => $organisationId,
            'channel_uid' => $channelUid,
            'scope_hash' => $scope,
            'destination_hash' => $identifier,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempt_count' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        RateLimiter::hit($limiter, 3600);
        RateLimiter::hit($requesterLimiter, 3600);
        RateLimiter::hit($orgLimiter, 3600);
        try {
            // This reuses the existing Meta sender, in its new strict
            // organisation-channel mode. No global login sender fallback.
            $this->sender->sendOtp($destination, $code, [
                'order_verification' => true,
                'organisation_id' => $organisationId,
                'channel_uid' => $channelUid,
            ]);
        } catch (\Throwable $e) {
            DB::table('order_otp_challenges')->where('id', $id)
                ->update(['status' => 'failed', 'updated_at' => now()]);
            Log::error('order_otp.delivery_failed', [
                'organisation_id' => $organisationId,
                'error_type' => $e::class,
            ]);
            return ['accepted' => false, 'challenge_id' => null];
        }
        Log::info('order_otp.sent', ['organisation_id' => $organisationId, 'store_id' => $storeId]);
        return ['accepted' => true, 'challenge_id' => $id];
    }

    public function verify(
        string $challengeId, string $code, int $organisationId, string $channelUid,
        string $chatbotUid, int $storeId, string $orderId,
        string $requesterPhone, string $orderPhone
    ): bool {
        $scope = $this->scope(
            $organisationId, $channelUid, $chatbotUid, $storeId, $orderId,
            $this->phone($requesterPhone), $this->phone($orderPhone)
        );
        if ($scope === '' || ! Str::isUuid($challengeId) || ! preg_match('/^[0-9]{6}$/D', $code)) {
            return false;
        }

        return DB::transaction(function () use ($challengeId, $code, $organisationId, $channelUid, $scope): bool {
            $challenge = DB::table('order_otp_challenges')
                ->where('id', $challengeId)
                ->where('organisation_id', $organisationId)
                ->where('channel_uid', $channelUid)
                ->where('scope_hash', $scope)
                ->lockForUpdate()->first();
            if (! $challenge || $challenge->status !== 'active' || Carbon::parse($challenge->expires_at)->isPast()
                || $challenge->attempt_count >= 5 || $challenge->consumed_at !== null) {
                return false;
            }

            $valid = Hash::check($code, $challenge->code_hash);
            $attempts = $challenge->attempt_count + 1;
            DB::table('order_otp_challenges')->where('id', $challengeId)->update([
                'attempt_count' => $attempts,
                'status' => $valid ? 'consumed' : ($attempts >= 5 ? 'locked' : 'active'),
                'consumed_at' => $valid ? now() : null,
                'updated_at' => now(),
            ]);
            Log::info('order_otp.verified', [
                'organisation_id' => $organisationId,
                'result' => $valid ? 'verified' : 'denied',
            ]);
            return $valid;
        });
    }

    private function phone(string $number): string
    {
        $value = $this->phones->normalize($number);
        return preg_match('/^\\+[1-9][0-9]{7,14}$/D', $value) ? $value : '';
    }

    private function scope(
        int $organisationId, string $channelUid, string $chatbotUid,
        int $storeId, string $orderId, string $requester, string $destination
    ): string {
        if ($organisationId <= 0 || $storeId <= 0 || $channelUid === '' || $chatbotUid === ''
            || $orderId === '' || strlen($orderId) > 128 || $requester === '' || $destination === '') {
            return '';
        }
        return hash('sha256', json_encode([
            $organisationId, $channelUid, $chatbotUid, $storeId,
            $orderId, $requester, $destination,
        ], JSON_THROW_ON_ERROR));
    }
}
