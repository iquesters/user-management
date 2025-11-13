<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Constants\EntityStatus;
use App\Logging\Logger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log; 
use Iquesters\UserManagement\Models\UserMeta;
use Iquesters\Foundation\Models\MasterData;


class ProfileController extends Controller
{
    /**
     * Save user meta data
     * 
     * @param int $userId
     * @param string $metaKey
     * @param string|null $metaValue
     * @return void
     */
    public function save_user_meta(int $userId, string $metaKey, ?string $metaValue): void
    {
        try {
            if (empty($metaValue)) {
                Log::warning("Meta value is empty for key '{$metaKey}' and user ID {$userId}.");
                return;
            }

            $meta = UserMeta::updateOrCreate(
                [
                    'ref_parent' => $userId,
                    'meta_key' => $metaKey,
                ],
                [
                    'meta_value' => $metaValue,
                    'status' => 'active',
                ]
            );

        } catch (\Exception $e) {
            Log::error("Failed to save user meta", [
                'user_id' => $userId,
                'meta_key' => $metaKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        // dd($request->all());
        $user = Auth::user();

        if (!$user) {
            return Redirect::route('login')->withErrors('User not authenticated.');
        }
        // Update only the user's name
        $user->name = $request->input('name');
        $user->save();

        // Save theme meta
        $this->save_user_meta(
            $user->id,
            'theme',
            $request->input('theme')
        );

        return Redirect::route('settings');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    /**
     * Update the user's profile picture.
     */
    public function updatePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            Logger::info('Attempting to update profile picture');

            $user = $request->user();

            if ($request->hasFile('profile_picture')) {
                // Deactivate previous profile picture
                $this->deactivatePreviousProfilePictures($user);

                // Upload new profile picture with unique filename
                $file = $request->file('profile_picture');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('profile-pictures', $filename, 'public');

                // Create new meta record
                UserMeta::create([
                    'ref_parent' => $user->id,
                    'meta_key' => 'profile_picture',
                    'meta_value' => $filename, // Store only filename
                    'status' => EntityStatus::ACTIVE,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                Logger::info('Profile picture updated successfully');
                return redirect()->back()->with('success', 'Profile picture updated successfully.');
            }

            Logger::warning('No profile picture file uploaded');
            return redirect()->back()->with('error', 'No file uploaded.');
        } catch (\Exception $e) {
            Logger::error('Failed to update profile picture', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to upload profile picture: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate previous profile pictures for a user.
     */
    protected function deactivatePreviousProfilePictures($user)
    {
        UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'profile_picture')
            ->where('status', EntityStatus::ACTIVE)
            ->update([
                'status' => EntityStatus::INACTIVE,
                'updated_by' => $user->id
            ]);
    }

    /**
     * Get the active profile picture for a user.
     */
    protected function getActiveProfilePicture($user)
    {
        return UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'profile_picture')
            ->where('status', EntityStatus::ACTIVE)
            ->first();
    }

    /**
     * Get the URL for a user's profile picture.
     */
    public static function getProfilePictureUrl($user)
    {
        $meta = UserMeta::where('ref_parent', $user->id)
            ->where('meta_key', 'profile_picture')
            ->where('status', EntityStatus::ACTIVE)
            ->first();

        if ($meta) {
            return route('profile.picture', ['filename' => $meta->meta_value]);
        }

        // Return default placeholder if no picture exists
        return 'https://placehold.co/400x400/faf3e0/d72638/png?text=' . ($user->name[0] ?? '?');
    }


    public function setting(){
        try{
            $user = Auth::user();
            $userMetas = UserMeta::where('ref_parent', $user->id)->pluck('meta_value', 'meta_key');

            $themes = MasterData::where('parent_id', 4)
            ->where('status', 'active')
            ->get(['id', 'key', 'value']);
            
            return view('usermanagement::profile.profile-setting',compact('user','userMetas','themes'));
        }catch(\Exception $e){
            Log::error('Failed to load profile settings', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to load profile settings: ' . $e->getMessage());
        }
        
    }
}