<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Iquesters\UserManagement\Services\GoogleIdTokenVerifier;
use Iquesters\UserManagement\Tests\TestCase;

class GoogleIdTokenVerifierTest extends TestCase
{
    private const CLIENT_ID = 'test-client.apps.googleusercontent.com';

    private string $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        // Test-only keys (tests/Fixtures); openssl_pkey_new() needs openssl.cnf, which Windows PHP often lacks.
        $this->privateKey = file_get_contents(__DIR__ . '/../Fixtures/google-test-key.pem');
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));

        $b64 = fn (string $bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
        $jwks = ['keys' => [[
            'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'kid' => 'test-kid',
            'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e']),
        ]]];

        Cache::forget('usermanagement.google_id_token_jwks');
        Http::fake([GoogleIdTokenVerifier::CERTS_URL => Http::response($jwks, 200, ['Cache-Control' => 'public, max-age=1200'])]);
    }

    private function token(array $overrides = [], ?string $key = null): string
    {
        $claims = $overrides + [
            'iss' => 'https://accounts.google.com',
            'aud' => self::CLIENT_ID,
            'sub' => '1234567890',
            'email' => 'asha@example.test',
            'email_verified' => true,
            'name' => 'Asha',
            'iat' => time(),
            'exp' => time() + 600,
        ];

        return JWT::encode($claims, $key ?? $this->privateKey, 'RS256', 'test-kid');
    }

    public function test_accepts_a_valid_token(): void
    {
        $payload = (new GoogleIdTokenVerifier)->verify($this->token(), self::CLIENT_ID);

        $this->assertSame('asha@example.test', $payload['email']);
        $this->assertSame('1234567890', $payload['sub']);
    }

    public function test_rejects_invalid_tokens(): void
    {
        $verifier = new GoogleIdTokenVerifier;

        $this->assertNull($verifier->verify($this->token(['aud' => 'someone-else']), self::CLIENT_ID), 'wrong audience');
        $this->assertNull($verifier->verify($this->token(['iss' => 'https://evil.example']), self::CLIENT_ID), 'wrong issuer');
        $this->assertNull($verifier->verify($this->token(['exp' => time() - 3600]), self::CLIENT_ID), 'expired');
        $this->assertNull($verifier->verify($this->token(['email_verified' => false]), self::CLIENT_ID), 'unverified email');
        $this->assertNull($verifier->verify('not-a-jwt', self::CLIENT_ID), 'garbage');
        $this->assertNull($verifier->verify(null, self::CLIENT_ID), 'missing');

        $otherPem = file_get_contents(__DIR__ . '/../Fixtures/other-test-key.pem');
        $this->assertNull($verifier->verify($this->token([], $otherPem), self::CLIENT_ID), 'bad signature');
    }

    public function test_google_keys_are_cached(): void
    {
        $verifier = new GoogleIdTokenVerifier;
        $verifier->verify($this->token(), self::CLIENT_ID);
        $verifier->verify($this->token(), self::CLIENT_ID);

        Http::assertSentCount(1);
    }
}
