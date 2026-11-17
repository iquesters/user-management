<?php

namespace Iquesters\UserManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\UserManagement\Support\SetupState;
use Symfony\Component\HttpFoundation\Response;

/**
 * Until the first-run setup is submitted, login/registration/password pages
 * are not reachable: browsers are sent to /setup, API clients get 409.
 */
class RequireSetup
{
    use Loggable;

    public function handle(Request $request, Closure $next): Response
    {
        if (SetupState::isConfigured()) {
            return $next($request);
        }

        static::logInfo('Setup not complete; sending request to /setup', ['path' => $request->path()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Application setup is not complete.',
                'redirect_url' => route('ui.setup', absolute: false),
            ], Response::HTTP_CONFLICT);
        }

        return redirect()->route('ui.setup');
    }
}
