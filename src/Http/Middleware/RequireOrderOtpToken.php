<?php

namespace Iquesters\UserManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Dedicated internal bearer; never use a login or operator token here. */
final class RequireOrderOtpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = trim((string) (config('services.order_otp.internal_token')
            ?: getenv('ORDER_OTP_INTERNAL_TOKEN') ?: ''));
        $presented = trim((string) $request->bearerToken());
        if (strlen($expected) < 32 || $presented === ''
            || ! hash_equals($expected, $presented)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }
        return $next($request);
    }
}
