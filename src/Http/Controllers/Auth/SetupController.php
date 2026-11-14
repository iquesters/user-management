<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Iquesters\Foundation\Models\MasterData;
use Iquesters\Foundation\Constants\EntityStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SetupController extends Controller
{
    public function show()
    {
        try {
            $superadminExists = $this->setupCompleted();

            Log::info('[Setup] Superadmin exists? ' . ($superadminExists ? 'Yes' : 'No'));

            if ($superadminExists) {
                return redirect()->route('register');
            }

            return view('usermanagement::auth.setup');
        } catch (\Throwable $e) {
            Log::error('[Setup] Error checking superadmin: ' . $e->getMessage());
            return redirect()->route('register');
        }
    }

    public function store(Request $request)
    {
        // Setup is a one-time step: once a super-admin exists, nobody (including
        // guests) may change the app name/logo through this endpoint.
        if ($this->setupCompleted()) {
            Log::warning('[Setup] Rejected: setup already completed.');

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Setup has already been completed.'], 403)
                : redirect()->route('register');
        }

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
            $filename = 'logo_' . Str::slug($validated['app_name']) . '.' . $extension;
            $logoFile->move(public_path('img'), $filename);
            $logoUrl = 'img/' . $filename; // store relative path

            Log::info('[Setup] Logo uploaded', ['path' => $logoUrl]);

            // ✅ Save configuration to master_data
            if (Schema::hasTable('master_data') && Schema::hasTable('master_data_metas')) {
                $this->saveAppConfigToMasterData($validated['app_name'], $logoUrl, $validated['description'] ?? '');
            } else {
                Log::warning('[Setup] master_data tables missing — skipping config save.');
            }

            // ✅ Clear all cache like `php artisan cache:clear`
            \Artisan::call('cache:clear');
            Log::info('[Setup] Cache cleared successfully after setup.');

            // Login/registration pages unlock from here on (see RequireSetup).
            \Iquesters\UserManagement\Support\SetupState::markConfigured();

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
            Log::error('[Setup] Failed: ' . $e->getMessage());

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

    /**
     * ✅ Save app configuration to master_data / master_data_metas in uppercase format
     */
    protected function saveAppConfigToMasterData(string $appName, string $logoPath, ?string $description): void
    {
        $userId = Auth::id();

        // 1️⃣ Ensure root config exists
        $configRoot = MasterData::firstOrCreate(
            ['key' => 'config'],
            [
                'value' => 'Application Configuration',
                'parent_id' => 0,
                'status' => EntityStatus::ACTIVE,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        // 2️⃣ Ensure module entry exists
        $moduleKey = 'user-interface-conf';
        $module = MasterData::firstOrCreate(
            ['key' => $moduleKey],
            [
                'value' => 'User Interface Configuration',
                'parent_id' => $configRoot->id,
                'status' => EntityStatus::ACTIVE,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        // 3️⃣ Save metas in UPPERCASE (like LOGO, APP_NAME, APP_DESCRIPTION)
        $module->setMetaBulk([
            'LOGO' => $logoPath,
            'APP_NAME' => $appName,
            'APP_DESCRIPTION' => $description ?? '',
        ], $userId);

        // 4️⃣ Clear cache
        $cacheKey = "conf_user_interface_flattened";
        Cache::forget($cacheKey);

        Log::info('[Setup] Master data metas saved', [
            'module' => $moduleKey,
            'LOGO' => $logoPath,
            'APP_NAME' => $appName,
            'APP_DESCRIPTION' => $description ?? '',
        ]);
    }
}