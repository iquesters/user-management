<?php

namespace Iquesters\UserManagement\Services;

use Iquesters\Foundation\Support\ConfigProvider;
use Iquesters\Foundation\Enums\Module;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected $secretKey;
    protected $siteKey;

    public function __construct()
    {
        $recaptcha = ConfigProvider::from(Module::USER_MGMT)->get('recaptcha');
    
        $this->secretKey = $recaptcha ? $recaptcha->secret_key : null;
        $this->siteKey = $recaptcha ? $recaptcha->site_key : null;

        // Debug logging
        Log::debug('RecaptchaService initialized', [
            'has_secret_key' => !empty($this->secretKey),
            'has_site_key' => !empty($this->siteKey),
            'recaptcha_config' => $recaptcha
        ]);
    }

    public function verify($token, $action = null, $minScore = 0.5)
    {
        Log::info('reCAPTCHA verification attempt', [
            'action' => $action,
            'min_score' => $minScore,
            'ip' => request()->ip(),
            'has_secret_key' => !empty($this->secretKey)
        ]);

        // Check if reCAPTCHA is configured
        if (empty($this->secretKey)) {
            Log::warning('reCAPTCHA secret key not configured');
            return [
                'success' => false,
                'error' => 'reCAPTCHA not configured properly'
            ];
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => request()->ip(),
        ]);

        $result = $response->json();

        Log::info('reCAPTCHA API response', $result);

        if (!$result['success']) {
            Log::warning('reCAPTCHA verification failed', $result);
            return [
                'success' => false,
                'error' => 'reCAPTCHA verification failed',
                'details' => $result['error-codes'] ?? []
            ];
        }

        // Check action if provided
        if ($action && isset($result['action']) && $result['action'] !== $action) {
            return [
                'success' => false,
                'error' => 'Action mismatch',
                'expected' => $action,
                'actual' => $result['action']
            ];
        }

        // Check score
        $score = $result['score'] ?? 0;
        if ($score < $minScore) {
            return [
                'success' => false,
                'error' => 'Score too low',
                'score' => $score,
                'threshold' => $minScore
            ];
        }

        return [
            'success' => true,
            'score' => $score,
            'action' => $result['action'] ?? null
        ];
    }

    public function getSiteKey()
    {
        return $this->siteKey;
    }

    /**
     * Check if reCAPTCHA is enabled and properly configured
     */
    public function isEnabled(): bool
    {
        $recaptcha = ConfigProvider::from(Module::USER_MGMT)->get('recaptcha');
        
        $enabled = $recaptcha ? $recaptcha->isEnabled() : false;
        $hasSecretKey = !empty($this->secretKey);
        $hasSiteKey = !empty($this->siteKey);
        
        Log::debug('reCAPTCHA enabled check', [
            'enabled' => $enabled,
            'has_secret_key' => $hasSecretKey,
            'has_site_key' => $hasSiteKey,
            'is_enabled' => $enabled && $hasSecretKey && $hasSiteKey
        ]);
        
        return $enabled && $hasSecretKey && $hasSiteKey;
    }
}