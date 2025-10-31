<?php

namespace Iquesters\UserManagement\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SetupController extends Controller
{
    public function show()
    {
        try {
            $superadminExists = false;

            // ✅ Only check if tables exist
            if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                $superadminExists = Role::where('name', 'super-admin')
                    ->whereHas('users')
                    ->exists();
            }

            Log::info('[Setup] Superadmin exists? ' . ($superadminExists ? 'Yes' : 'No'));

            // ✅ If super-admin exists, go directly to registration
            if ($superadminExists) {
                return redirect()->route('register');
            }

            // Otherwise, show setup form
            return view('usermanagement::auth.setup');

        } catch (\Throwable $e) {
            // ⚠️ Catch *any* unexpected issue (DB missing, permission table not found, etc.)
            Log::error('[Setup] Failed to check for superadmin: ' . $e->getMessage());

            // Fallback — assume setup done and continue to registration safely
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
        // ✅ Save logo in public/img folder
        $logoFile = $request->file('logo');
        $filename = 'logo_' . Str::slug($validated['app_name']) . '.' . $logoFile->getClientOriginalExtension();
        $path = $logoFile->move(public_path('img'), $filename);
        $logoUrl = asset('img/' . $filename);

        // ✅ Write setup data to .env file
        $this->setEnvValues([
            'APP_NAME' => $validated['app_name'],
            'APP_LOGO' => $logoUrl,
            'APP_DESCRIPTION' => $validated['description'] ?? '',
        ]);

        Log::info('[Setup] Application setup completed', [
            'app_name' => $validated['app_name'],
            'logo' => $logoUrl,
        ]);

        return redirect()
            ->route('register')
            ->with('success', 'Setup completed! Please create your Superadmin account.');

    } catch (\Throwable $e) {
        Log::error('[Setup] Failed: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Setup failed: ' . $e->getMessage()]);
    }
}

/**
 * ✅ Helper method to write/update keys in .env file
 */
protected function setEnvValues(array $values)
{
    $envPath = base_path('.env');
    $envContent = file_get_contents($envPath);

    foreach ($values as $key => $value) {
        $pattern = "/^{$key}=.*/m";
        $line = "{$key}=\"{$value}\"";

        if (preg_match($pattern, $envContent)) {
            // Replace existing value
            $envContent = preg_replace($pattern, $line, $envContent);
        } else {
            // Append if not exists
            $envContent .= "\n{$line}";
        }
    }

    file_put_contents($envPath, $envContent);
}

}