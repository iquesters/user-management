<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Iquesters\Foundation\Models\MasterData;
use Iquesters\Foundation\Support\UserModel;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\UserManagement\Models\UserMeta;

class ProfileController extends Controller
{
    use Loggable;

    /** My profile: picture, contact details, roles. */
    public function myprofile(): View
    {
        $user = Auth::user();

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();

        // Everything on the page comes from the "user-profile" schema (view mode);
        // these extra values feed its view-only fields.
        return view('usermanagement::profile.my-profile', [
            'user' => $user,
            'profileData' => $this->profileData($user) + [
                'picture' => $user->avatarUrl(),
                'status' => $user->status,
                'email_status' => $user->email ? ($user->email_verified_at ? 'Verified' : 'Not verified') : '',
                'roles' => $roles->map(fn ($role) => ucwords(str_replace('-', ' ', $role)))->values()->all(),
                'member_since' => $user->created_at?->format('d M Y'),
                'previous_login' => $this->formatLoginTime($user->getMeta('last_login_at')) ?? 'This is your first sign-in',
            ],
        ]);
    }

    /** Settings: basic information, password, delete account. */
    public function settings(): View
    {
        $user = Auth::user();

        return view('usermanagement::profile.profile-setting', [
            'user' => $user,
            'profileData' => $this->profileData($user),
            'hasPassword' => $user->hasUsablePassword(),
        ]);
    }

    /** Values of the "user-profile" form schema (both modes). */
    private function profileData($user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'theme' => $user->getMeta('theme') ?? '',
        ];
    }

    /** Update name and theme (schema form "user-profile", or a plain form post). */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'theme' => ['nullable', 'string', Rule::in($this->themes()->pluck('key'))],
        ]);

        $user = $request->user();
        $user->update(['name' => $validated['name']]);

        // Empty theme = back to the application default.
        if (empty($validated['theme'])) {
            UserMeta::where('ref_parent', $user->id)->where('meta_key', 'theme')->delete();
        } else {
            UserMeta::updateOrCreate(
                ['ref_parent' => $user->id, 'meta_key' => 'theme'],
                ['meta_value' => $validated['theme'], 'status' => 'active']
            );
        }

        return $this->done($request, 'Profile updated.', route('settings', absolute: false));
    }

    /**
     * New profile picture (form schema "user-profile-picture", in a modal).
     * Cropped to a centred square and scaled to at most 512px, so any photo
     * fits the square frame; the previous picture is replaced.
     */
    public function updatePicture(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $user = $request->user();
        $filename = $this->storeSquarePicture($request->file('picture'));

        UserMeta::updateOrCreate(
            ['ref_parent' => $user->id, 'meta_key' => 'profile_picture'],
            ['meta_value' => $filename, 'status' => 'active']
        );

        static::logInfo('Profile picture updated', ['user_id' => $user->id]);

        return $this->done($request, 'Profile picture updated.', route('myprofile', absolute: false));
    }

    /** Centre-crop to a square, scale to at most 512px, save as PNG; returns the file name. */
    private function storeSquarePicture(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw ValidationException::withMessages(['picture' => 'This image could not be read.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $target = min(512, $side);

        $square = imagecreatetruecolor($target, $target);
        imagealphablending($square, false);
        imagesavealpha($square, true);
        imagecopyresampled($square, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), $target, $target, $side, $side);

        ob_start();
        imagepng($square);
        $png = (string) ob_get_clean();
        imagedestroy($square);
        imagedestroy($source);

        $filename = Str::random(40).'.png';
        Storage::disk('public')->put('media/'.$filename, $png);

        return $filename;
    }

    /**
     * Schema forms submit with fetch() and navigate to redirect_url; the message
     * is flashed so the reloaded page shows it as a toast.
     */
    private function done(Request $request, string $message, string $redirectUrl): RedirectResponse|JsonResponse
    {
        if (! $request->wantsJson()) {
            return Redirect::to($redirectUrl)->with('success', $message);
        }

        session()->flash('success', $message);

        return response()->json(['success' => true, 'message' => $message, 'redirect_url' => $redirectUrl]);
    }

    /**
     * Delete the signed-in user's account. Users without a password (Google or
     * OTP sign-in) confirm with their email address instead.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $request->validateWithBag('userDeletion', $user->hasUsablePassword()
            ? ['password' => ['required', 'current_password']]
            : ['confirm_email' => ['required', Rule::in([$user->email])]],
            ['confirm_email.in' => 'Type your email address exactly to confirm.']);

        if ($this->isLastSuperAdmin($user)) {
            $message = 'You are the only super admin. Make another user super admin before deleting your account.';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : Redirect::route('settings', ['tab' => 'delete'])->with('error', $message);
        }

        static::logInfo('User deleted their account', ['user_id' => $user->id]);

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Your account has been deleted.', 'redirect_url' => '/'])
            : Redirect::to('/');
    }

    private function isLastSuperAdmin($user): bool
    {
        if (! method_exists($user, 'hasRole') || ! $user->hasRole('super-admin')) {
            return false;
        }

        return UserModel::query()->role('super-admin')->count() <= 1;
    }

    /** Selectable themes (master data children of "theme"). */
    private function themes(): Collection
    {
        $parentId = MasterData::where('key', 'theme')->where('status', 'active')->value('id');

        if (! $parentId) {
            return collect();
        }

        return MasterData::where('parent_id', $parentId)
            ->where('status', 'active')
            ->where('key', '!=', 'current_theme')
            ->orderBy('value')
            ->get(['key', 'value']);
    }

    private function formatLoginTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return null;
        }
    }
}
