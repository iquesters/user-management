<?php

namespace Iquesters\UserManagement\Support;

/**
 * Guards user-supplied "redirect after login" URLs against open redirects.
 *
 * Accepted: a path on this site ("/dashboard?tab=1") or an absolute http(s)
 * URL on the app's own host (scheme, host and port of the current request or
 * APP_URL). Everything else (other hosts, "//evil.example", "/\evil.example",
 * "javascript:", "data:", control characters) is rejected.
 */
final class SafeRedirect
{
    /** The URL if it stays on this site, otherwise null. */
    public static function url(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        // Browsers ignore tabs/newlines inside URLs and treat "\" like "/".
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F\\\\]/', $url)) {
            return null;
        }

        // Relative path: one leading slash, not "//host" (protocol-relative).
        if ($url[0] === '/') {
            return str_starts_with($url, '//') ? null : $url;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return in_array(self::origin($parts), self::allowedOrigins(), true) ? $url : null;
    }

    /** @return list<string> */
    private static function allowedOrigins(): array
    {
        $origins = [];

        foreach ([request()->getSchemeAndHttpHost(), config('app.url')] as $base) {
            $parts = is_string($base) ? parse_url($base) : false;
            if ($parts && isset($parts['scheme'], $parts['host'])) {
                $origins[] = self::origin($parts);
            }
        }

        return array_values(array_unique($origins));
    }

    private static function origin(array $parts): string
    {
        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return $scheme.'://'.strtolower($parts['host']).':'.$port;
    }
}
