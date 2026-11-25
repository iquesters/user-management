<?php

namespace Iquesters\UserManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Iquesters\UserManagement\Support\Terms;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in people who haven't agreed to the current Terms / Privacy policy
 * (Google or OTP sign-up, accounts made by an admin, or a new terms_version)
 * are sent to the accept page once, then back where they were going.
 *
 * Only page loads (GET, not JSON) are redirected; the accept page, logout and
 * the legal pages themselves stay reachable.
 */
class EnsureTermsAccepted
{
    private const OPEN_ROUTES = ['terms.accept', 'terms.accept.store', 'logout', 'ui.terms', 'ui.privacy', 'ui.help'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || ! $request->isMethod('GET')
            || $request->expectsJson()
            || in_array($request->route()?->getName(), self::OPEN_ROUTES, true)
            || ! Terms::required()
            || Terms::hasAccepted($user)) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('terms.accept');
    }
}
