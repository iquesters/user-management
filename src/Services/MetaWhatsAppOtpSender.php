<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;

class MetaWhatsAppOtpSender implements WhatsAppOtpSender
{
    public function __construct(
        protected PhoneNumberService $phoneNumberService
    ) {
    }

    public function sendOtp(string $phone, string $otp, array $context = []): array
    {
        $config = ConfProvider::from(Module::USER_MGMT)->whatsapp_login;
        $phoneNumberId = trim((string) ($config->phone_number_id ?? ''));
        $accessToken = trim((string) ($config->access_token ?? ''));
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

            throw new \RuntimeException('Meta WhatsApp OTP request failed with status ' . $response->status() . '.');
        }

        $responseData = $response->json();
        $providerMessageId = data_get($responseData, 'messages.0.id')
            ?? data_get($responseData, 'message_id')
            ?? ('meta-wa-' . Str::uuid()->toString());

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

    protected function sanitizeResponseForLogs($responseBody): array
    {
        if (!is_array($responseBody)) {
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
