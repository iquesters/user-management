<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\UserManagement\Support\Terms;

/** The accept page (form schema "accept-terms"): agree to the current Terms and Privacy policy. */
class TermsController extends Controller
{
    use Loggable;

    public const FORM = 'accept-terms';

    public function show(Request $request): View|RedirectResponse
    {
        if (! Terms::required() || Terms::hasAccepted($request->user())) {
            return redirect()->intended(route('dashboard'));
        }

        return view('usermanagement::auth.accept-terms', ['formId' => self::FORM]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'Please tick the box to agree.']);

        Terms::record($request->user());
        static::logInfo('Terms accepted', ['user_id' => $request->user()->id, 'version' => Terms::version()]);

        $url = $request->session()->pull('url.intended', route('dashboard'));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Thank you.', 'redirect_url' => $url])
            : redirect()->to($url);
    }
}
