<?php

namespace Iquesters\UserManagement\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Iquesters\UserManagement\Models\UserMeta;
use Illuminate\Support\Str;

abstract class BaseAuthHelper
{
    /**
     * Generate a unique ULID for user
     * 
     * @return string
     */
    protected static function generate_uid(): string
    {
        return Str::ulid();
    }

    /**
     * Save user meta data
     * 
     * @param int $userId
     * @param string $metaKey
     * @param string|null $metaValue
     * @return void
     */
    protected static function save_user_meta(int $userId, string $metaKey, ?string $metaValue): void
    {
        if (empty($metaValue)) {
            return;
        }

        UserMeta::updateOrCreate(
            [
                'ref_parent' => $userId,
                'meta_key' => $metaKey,
            ],
            [
                'meta_value' => $metaValue,
                'status' => 'active',
            ]
        );
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    protected static function get_client_ip(): string
    {
        // Check for shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for proxied requests
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Can be multiple IPs, take the first one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        // Fall back to remote address
        else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    /**
     * Get user agent string
     * 
     * @return string
     */
    protected static function get_user_agent(): string
    {
        return Request::header('User-Agent') ?? 'Unknown';
    }

    /**
     * Get country from IP using free API
     * 
     * @return string
     */
    protected static function get_country(): string
    {
        try {
            $ip = self::get_client_ip();
            
            // Skip localhost
            if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0') {
                return 'LOCAL';
            }

            // Try to get country from ip-api.com (free, no API key needed)
            $timeout = stream_context_create([
                'http' => ['timeout' => 3]
            ]);
            
            $response = @file_get_contents("http://ip-api.com/json/{$ip}", false, $timeout);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['countryCode'])) {
                    return $data['countryCode'];
                }
            }

            return 'UNKNOWN';
        } catch (\Exception $e) {
            Log::warning('Could not determine country: ' . $e->getMessage());
            return 'UNKNOWN';
        }
    }

    /**
     * Get locale/language
     * 
     * @return string
     */
    protected static function get_locale(): string
    {
        return config('app.locale') ?? app()->getLocale() ?? 'en';
    }

    /**
     * Get timezone
     * 
     * @return string
     */
    protected static function get_timezone(): string
    {
        return config('app.timezone') ?? 'UTC';
    }
}