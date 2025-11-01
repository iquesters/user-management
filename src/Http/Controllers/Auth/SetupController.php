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
            $superadminExists = false;

            if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                $superadminExists = Role::where('name', 'super-admin')
                    ->whereHas('users')
                    ->exists();
            }

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
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'logo' => 'required|image|max:2048',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            // ✅ Upload logo
            $logoFile = $request->file('logo');
            $filename = 'logo_' . Str::slug($validated['app_name']) . '.' . $logoFile->getClientOriginalExtension();
            $path = $logoFile->move(public_path('img'), $filename);
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

            return redirect()
                ->route('register')
                ->with('success', 'Setup completed successfully! Please create your Superadmin account.');

        } catch (\Throwable $e) {
            Log::error('[Setup] Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Setup failed: ' . $e->getMessage()]);
        }
    }


    /**
     * ✅ Save app configuration to master_data / master_data_metas in uppercase format
     */
    protected function saveAppConfigToMasterData(string $appName, string $logoPath, ?string $description): void
    {
        $userId = Auth::id() ?? 0;

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