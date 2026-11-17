<?php

namespace Iquesters\UserManagement\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Iquesters\Foundation\System\Traits\Loggable;
use Throwable;

/**
 * Verifies Google ID tokens (Sign in with Google / One Tap) without the
 * google/apiclient package: RS256 signature against Google's published keys,
 * issuer, audience (our client id), expiry, and a verified email address.
 */
class GoogleIdTokenVerifier
{
    use Loggable;

    public const CERTS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    private const CACHE_KEY = 'usermanagement.google_id_token_jwks';

    /** @return array<string, mixed>|null the token payload, or null if the token is not valid */
    public function verify(?string $idToken, string $clientId): ?array
    {
        if (! $idToken || $clientId === '') {
            return null;
        }

        try {
            $previousLeeway = JWT::$leeway;
            JWT::$leeway = 60;

            try {
                $payload = (array) JWT::decode($idToken, JWK::parseKeySet($this->keys(), 'RS256'));
            } finally {
                JWT::$leeway = $previousLeeway;
            }
        } catch (Throwable $e) {
            static::logWarning('Google ID token rejected: '.$e->getMessage());

            return null;
        }

        $audience = (array) ($payload['aud'] ?? []);

        if (! in_array($payload['iss'] ?? null, self::ISSUERS, true)
            || ! in_array($clientId, $audience, true)
            || empty($payload['sub'])
            || empty($payload['email'])
            || ! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            static::logWarning('Google ID token rejected: issuer, audience or email verification check failed.');

            return null;
        }

        return $payload;
    }

    /** Google's JWKS, cached for the max-age Google sends (default 1 hour). */
    private function keys(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && ! empty($cached['keys'])) {
            return $cached;
        }

        $response = Http::timeout(5)->acceptJson()->get(self::CERTS_URL)->throw();
        $jwks = $response->json();

        $ttl = 3600;
        if (preg_match('/max-age=(\d+)/', (string) $response->header('Cache-Control'), $m)) {
            $ttl = max(60, (int) $m[1]);
        }

        Cache::put(self::CACHE_KEY, $jwks, $ttl);

        return $jwks;
    }
}
