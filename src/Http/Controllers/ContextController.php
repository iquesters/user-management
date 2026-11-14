<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Iquesters\Foundation\System\Traits\Loggable;
use Illuminate\Routing\Controller;
use App\Models\Event;
use App\Models\Organization;
use Iquesters\Foundation\Support\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class ContextController extends Controller
{
    use Loggable;

    public static function get_all_context()
    {
        return (object)array(
            'user' => self::get_user(),
            'org' => self::get_org(),
            'event' => self::get_event(),
        );
    }

    public static function get_user()
    {
        return Session::get('current_user');
    }
    public static function get_org()
    {
        return Session::get('current_org');
    }
    public static function get_event()
    {
        return Session::get('current_event');
    }

    public static function get_context_model()
    {
        $model = UserModel::name();
        $model_id = Auth::user()->id;
        $event_ctx = self::get_event();
        if (isset($event_ctx)) {
            $model = Event::class;
            $model_id = $event_ctx->id;
        } else {
            $org_ctx = self::get_org();
            if (isset($org_ctx)) {
                $model = Organization::class;
                $model_id = $org_ctx->id;
            }
        }
        return (object)([
            'model' => $model,
            'model_id' => $model_id
        ]);
    }


    public static function set_org($bizID)
    {
        $organization = Organization::find($bizID);
        static::logDebug("bizID = " . $bizID);
        static::logDebug('Organization', ['organization' => $organization]);
        Session::put('current_org', $organization);
    }

    public function set_org_in_ctx($bizID)
    {
        ContextController::set_org($bizID);
        return redirect()->back();
    }
    public function remove_org()
    {
        Session::remove('current_org');

        return redirect()->back();
    }

    public static function set_event($eventID)
    {
        $event = Event::find($eventID);
        static::logDebug('Event', ['event' => $event]);
        Session::put('current_event', $event);
    }

    public function set_event_in_ctx($eventID)
    {
        ContextController::set_event($eventID);
        return redirect()->back();
    }

    public function remove_event()
    {
        Session::remove('current_event');
        return redirect()->back();
    }

    /**
     * This function sets the redirect url in the session. If the redirect url is the same as the base url, then the redirect url is removed from the session.
     * @param $redirect_url
     * @return void
     */
    public static function set_redirect_url($redirect_url)
    {
        $base_url = URL::to('/') . '/';
        static::logInfo('Redirect URL: ' . $redirect_url);
        static::logInfo('Base URL: ' . $base_url);
        if ($redirect_url != null && $redirect_url != $base_url) {
            Session::put('redirect_url', $redirect_url);
        } else {
            Session::forget('redirect_url');
        }
    }
}
