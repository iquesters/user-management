<?php

namespace Iquesters\UserManagement\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected $secretKey;
    protected $siteKey;

    public function __construct()
    {
        $this->secretKey = config('usermanagement.recaptcha.secret_key');
        $this->siteKey   = config('usermanagement.recaptcha.site_key');
    }

    public function verify($token, $action = null, $minScore = 0.5)
    {
        Log::info('reCAPTCHA verification attempt', [
            'action' => $action,
            'min_score' => $minScore,
            'ip' => request()->ip()
        ]);

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
}