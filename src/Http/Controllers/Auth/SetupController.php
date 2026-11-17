<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\UserManagement\Support\SetupState;
use Spatie\Permission\Models\Role;

class SetupController extends Controller
{
    use Loggable;

    public function show()
    {
        try {
            $superadminExists = $this->setupCompleted();

            if ($superadminExists) {
                static::logInfo('Setup already completed (super-admin exists); redirecting to register');

                return redirect()->route('register');
            }

            static::logInfo('Setup step 1/4: setup page shown');

            return view('usermanagement::auth.setup');
        } catch (\Throwable $e) {
            static::logError('Could not check setup state: '.$e->getMessage());

            return redirect()->route('register');
        }
    }

    public function store(Request $request)
    {
        // Setup is a one-time step: once a super-admin exists, nobody (including
        // guests) may change the app name/logo through this endpoint.
        if ($this->setupCompleted()) {
            static::logWarning('Setup submission rejected: setup already completed (super-admin exists)', ['ip' => $request->ip()]);

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Setup has already been completed.'], 403)
                : redirect()->route('register');
        }

        static::logInfo('Setup step 2/4: setup form submitted', ['app_name' => $request->input('app_name'), 'via' => $request->wantsJson() ? 'schema form' : 'classic form']);

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            // Real image types only (checked by content); SVG excluded (can carry scripts).
            'logo' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            // Extension comes from the file content, never from the client's file name,
            // so a "logo.php" can't be written into public/.
            $logoFile = $request->file('logo');
            $extension = $logoFile->guessExtension() ?: 'png';
            $filename = 'logo_'.Str::slug($validated['app_name']).'.'.$extension;
            $logoFile->move(public_path('img'), $filename);
            $logoUrl = 'img/'.$filename; // store relative path

            static::logInfo('Setup step 3/4: logo saved', ['path' => $logoUrl]);

            // Name, logo and description are user-interface settings (validated, stored in module_settings).
            ConfProvider::from(Module::USER_INFE)->update([
                'app_name' => $validated['app_name'],
                'logo' => $logoUrl,
                'app_description' => $validated['description'] ?? '',
            ], Auth::id());

            static::logInfo('Application settings saved (name, logo, description)', [
                'app_name' => $validated['app_name'],
                'logo' => $logoUrl,
            ]);

            // ✅ Clear all cache like `php artisan cache:clear`
            \Artisan::call('cache:clear');
            static::logDebug('Cache cleared after setup');

            // Login/registration pages unlock from here on (see RequireSetup).
            SetupState::markConfigured();

            static::logInfo('Setup step 4/4: setup complete; next: create the super-admin account', ['app_name' => $validated['app_name']]);

            $message = 'Setup completed successfully! Please create your Superadmin account.';

            // Schema-rendered forms submit via fetch() and navigate using redirect_url.
            if ($request->wantsJson()) {
                session()->flash('success', $message);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => route('register', absolute: false),
                ]);
            }

            return redirect()->route('register')->with('success', $message);

        } catch (\Throwable $e) {
            static::logError('Setup failed: '.$e->getMessage(), ['exception' => $e]);

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Setup failed. Please try again.'], 500)
                : back()->withErrors(['error' => 'Setup failed. Please try again.']);
        }
    }

    protected function setupCompleted(): bool
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return false;
        }

        return Role::where('name', 'super-admin')->whereHas('users')->exists();
    }
}
