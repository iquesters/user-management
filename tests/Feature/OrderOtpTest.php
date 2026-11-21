<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;
use Iquesters\UserManagement\Services\OrderOtpService;
use Iquesters\UserManagement\Tests\TestCase;

class FakeOrderOtpSender implements WhatsAppOtpSender
{
    public array $messages = [];

    public function sendOtp(string $phone, string $otp, array $context = []): array
    {
        $this->messages[] = compact('phone', 'otp', 'context');
        return ['provider_message_id' => 'unit-test', 'delivery_status' => 'sent'];
    }
}

class OrderOtpTest extends TestCase
{
    private FakeOrderOtpSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('order_otp_challenges')) {
            Schema::create('order_otp_challenges', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->unsignedBigInteger('organisation_id');
                $t->string('channel_uid', 64);
                $t->char('scope_hash', 64);
                $t->char('destination_hash', 64);
                $t->text('code_hash');
                $t->timestamp('expires_at');
                $t->unsignedTinyInteger('attempt_count')->default(0);
                $t->string('status', 16)->default('active');
                $t->timestamp('consumed_at')->nullable();
                $t->timestamps();
            });
        }
        $this->sender = new FakeOrderOtpSender;
        $this->app->instance(WhatsAppOtpSender::class, $this->sender);
    }

    private function issue(string $senderPhone = '+15105550123', string $orderPhone = '+14155550199'): array
    {
        return app(OrderOtpService::class)->issue(
            7, 'channel-nams', 'bot-nams', 1001, '1234', $senderPhone, $orderPhone
        );
    }

    public function test_mismatched_sender_receives_order_specific_challenge(): void
    {
        $result = $this->issue();
        $this->assertTrue($result['accepted']);
        $this->assertCount(1, $this->sender->messages);
        $this->assertSame('+14155550199', $this->sender->messages[0]['phone']);
        $this->assertTrue($this->sender->messages[0]['context']['order_verification']);
        $this->assertSame(7, $this->sender->messages[0]['context']['organisation_id']);
        $this->assertSame('channel-nams', $this->sender->messages[0]['context']['channel_uid']);

        $otp = $this->sender->messages[0]['otp'];
        $svc = app(OrderOtpService::class);
        $id = $result['challenge_id'];
        $this->assertFalse($svc->verify($id, $otp, 7, 'channel-nams', 'bot-nams', 1002, '1234',
            '+15105550123', '+14155550199'), 'OTP must not authorize a second store');
        $this->assertFalse($svc->verify($id, $otp, 7, 'channel-nams', 'bot-nams', 1001, '9999',
            '+15105550123', '+14155550199'), 'OTP must not authorize a second order');
        $this->assertFalse($svc->verify($id, $otp, 7, 'foreign', 'bot-nams', 1001, '1234',
            '+15105550123', '+14155550199'), 'OTP must not authorize a foreign channel');
        $this->assertTrue($svc->verify($id, $otp, 7, 'channel-nams', 'bot-nams', 1001, '1234',
            '+15105550123', '+14155550199'));
        $this->assertFalse($svc->verify($id, $otp, 7, 'channel-nams', 'bot-nams', 1001, '1234',
            '+15105550123', '+14155550199'), 'OTP cannot be reused');
    }

    public function test_matching_order_phone_never_sends_otp(): void
    {
        $this->assertFalse($this->issue('+15105550123', '+15105550123')['accepted']);
        $this->assertEmpty($this->sender->messages);
    }

    public function test_invalid_code_locks_after_five_attempts(): void
    {
        $issued = $this->issue();
        $code = $this->sender->messages[0]['otp'];
        $wrong = $code === '000000' ? '999999' : '000000';
        for ($i = 0; $i < 5; ++$i) {
            $this->assertFalse(app(OrderOtpService::class)->verify(
                $issued['challenge_id'], $wrong, 7, 'channel-nams', 'bot-nams',
                1001, '1234', '+15105550123', '+14155550199'
            ));
        }
        $this->assertFalse(app(OrderOtpService::class)->verify(
            $issued['challenge_id'], $code, 7, 'channel-nams', 'bot-nams',
            1001, '1234', '+15105550123', '+14155550199'
        ), 'Locked challenge cannot be unlocked by later correct code');
    }

    public function test_internal_routes_reject_missing_token(): void
    {
        $this->postJson('/v1/internal/order-otp/issue', [
            'organisation_id' => 7,
        ])->assertStatus(401);
    }
}
