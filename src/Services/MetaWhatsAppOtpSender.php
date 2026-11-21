<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;

class MetaWhatsAppOtpSender implements WhatsAppOtpSender
{
    public function __construct(
        protected PhoneNumberService $phoneNumberService
    ) {}

    public function sendOtp(string $phone, string $otp, array $context = []): array
    {
        $config = ConfProvider::from(Module::USER_MGMT)->whatsapp_login;
        $phoneNumberId = trim((string) ($config->phone_number_id ?? ''));
        $accessToken = trim((string) ($config->access_token ?? ''));

        // Order verification must use the organisation's receiving WhatsApp
        // channel, not the platform-wide login sender. Never fall back.
        if (array_key_exists('order_verification', $context)) {
            if ($context['order_verification'] !== true) {
                throw new \InvalidArgumentException('Invalid order verification context.');
            }
            [$phoneNumberId, $accessToken] = $this->organisationSender($context);
        }
        $templateName = trim((string) ($context['template_name'] ?? $config->verify_template_name ?? ''));
        $templateLanguageCode = trim((string) ($config->template_language_code ?? 'en_US'));
        $graphVersion = trim((string) ($config->graph_version ?? 'v23.0'));
        $maskedPhone = $this->phoneNumberService->mask($phone);
        $normalizedPhone = ltrim($this->phoneNumberService->normalize($phone), '+');

        if ($phoneNumberId === '' || $accessToken === '' || $templateName === '') {
            Log::error('Meta WhatsApp OTP sender is missing required configuration.', [
                'auth_method' => 'whatsapp_otp',
                'operation' => 'send_otp',
                'provider' => 'meta',
                'masked_phone' => $maskedPhone,
                'has_phone_number_id' => $phoneNumberId !== '',
                'has_access_token' => $accessToken !== '',
                'has_template_name' => $templateName !== '',
            ]);

            throw new \RuntimeException('Meta WhatsApp OTP sender is not fully configured.');
        }

        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', $graphVersion, $phoneNumberId);
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalizedPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $templateLanguageCode,
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otp,
                            ],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otp,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Log::info('Dispatching WhatsApp OTP through Meta Cloud API.', [
            'auth_method' => 'whatsapp_otp',
            'operation' => 'send_otp',
            'provider' => 'meta',
            'masked_phone' => $maskedPhone,
            'template_name' => $templateName,
            'template_language_code' => $templateLanguageCode,
            'graph_version' => $graphVersion,
        ]);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error('Meta WhatsApp OTP send failed.', [
                'auth_method' => 'whatsapp_otp',
                'operation' => 'send_otp',
                'provider' => 'meta',
                'masked_phone' => $maskedPhone,
                'status_code' => $response->status(),
                'response_body' => $this->sanitizeResponseForLogs($response->json()),
            ]);

            throw new \RuntimeException('Meta WhatsApp OTP request failed with status '.$response->status().'.');
        }

        $responseData = $response->json();
        $providerMessageId = data_get($responseData, 'messages.0.id')
            ?? data_get($responseData, 'message_id')
            ?? ('meta-wa-'.Str::uuid()->toString());

        Log::info('Meta WhatsApp OTP send succeeded.', [
            'auth_method' => 'whatsapp_otp',
            'operation' => 'send_otp',
            'provider' => 'meta',
            'masked_phone' => $maskedPhone,
            'provider_message_id' => $providerMessageId,
        ]);

        return [
            'provider_message_id' => $providerMessageId,
            'delivery_status' => 'sent',
        ];
    }

    /**
     * Resolve a tenant-owned *active* WhatsApp channel using the trusted
     * organisation and receiving channel ids supplied by the internal service.
     * Never use global whatsapp_login credentials for order OTP.
     */
    private function organisationSender(array $context): array
    {
        $orgId = $context['organisation_id'] ?? null;
        $channelUid = $context['channel_uid'] ?? null;
        if (! is_int($orgId) || $orgId <= 0 || ! is_string($channelUid)
            || $channelUid === '' || strlen($channelUid) > 64) {
            throw new \RuntimeException('order_otp_channel_scope_missing');
        }

        $channel = DB::table('channels')
            ->where('uid', $channelUid)
            ->where('status', 'active')
            ->first();
        if (! $channel || (string) $channel->uid !== $channelUid) {
            throw new \RuntimeException('order_otp_channel_unavailable');
        }
        $provider = DB::table('channel_providers')
            ->where('id', $channel->channel_provider_id)
            ->where('small_name', 'whatsapp')
            ->where('status', 'active')
            ->first();
        if (! $provider) {
            throw new \RuntimeException('order_otp_channel_provider_not_authorized');
        }

        // A shared or foreign channel must not send OTP as this organisation.
        $memberships = DB::table('model_has_organisations')
            ->where('model_id', $channel->id)
            ->where('model_type', 'Iquesters\\SmartMessenger\\Models\\Channel')
            ->get(['organisation_id', 'model_type']);
        $valid = $memberships->filter(fn ($row) =>
            $row->model_type === 'Iquesters\\SmartMessenger\\Models\\Channel'
            && (int) $row->organisation_id === $orgId
        );
        if ($memberships->count() !== 1 || $valid->count() !== 1) {
            throw new \RuntimeException('order_otp_channel_not_authorized');
        }

        $metas = DB::table('channel_metas')
            ->where('ref_parent', $channel->id)
            ->whereIn('meta_key', ['whatsapp_phone_number_id', 'system_user_token'])
            ->pluck('meta_value', 'meta_key');
        $id = trim((string) ($metas['whatsapp_phone_number_id'] ?? ''));
        $token = trim((string) ($metas['system_user_token'] ?? ''));
        if ($id === '' || $token === '') {
            throw new \RuntimeException('order_otp_channel_credentials_unavailable');
        }
        Log::info('Order OTP organisation sender selected.', [
            'organisation_id' => $orgId,
            'channel_id' => $channel->id,
        ]);
        return [$id, $token];
    }

    protected function sanitizeResponseForLogs($responseBody): array
    {
        if (! is_array($responseBody)) {
            return [
                'raw' => is_scalar($responseBody) ? (string) $responseBody : 'non_array_response',
            ];
        }

        return [
            'error' => data_get($responseBody, 'error.message'),
            'error_type' => data_get($responseBody, 'error.type'),
            'error_code' => data_get($responseBody, 'error.code'),
            'fbtrace_id' => data_get($responseBody, 'error.fbtrace_id'),
        ];
    }
}
