<?php

namespace Iquesters\UserManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Models\UserMeta;

/**
 * The platform's Terms and Privacy policy, and who has agreed to which version.
 *
 * The pages: user-interface's privacy_url / terms_url when set, else its own
 * pages (/privacy-policy, /terms-conditions). The version is
 * user-management's terms_version; agreeing stores it, with the time, as user
 * metas (terms_version, terms_accepted_at). A new version asks everyone again.
 */
class Terms
{
    public const META_VERSION = 'terms_version';

    public const META_ACCEPTED_AT = 'terms_accepted_at';

    public static function required(): bool
    {
        try {
            return (bool) ConfProvider::from(Module::USER_MGMT)->terms_required;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function version(): string
    {
        return (string) ConfProvider::from(Module::USER_MGMT)->terms_version;
    }

    /** @return array<int, array{label: string, url: string}> */
    public static function links(): array
    {
        $terms = $privacy = '';
        try {
            $ui = ConfProvider::from(Module::USER_INFE);
            $terms = trim((string) $ui->terms_url);
            $privacy = trim((string) $ui->privacy_url);
        } catch (\Throwable $e) {
            // user-interface not installed: its pages don't exist either.
        }

        $terms = $terms !== '' ? $terms : (Route::has('ui.terms') ? route('ui.terms', absolute: false) : '');
        $privacy = $privacy !== '' ? $privacy : (Route::has('ui.privacy') ? route('ui.privacy', absolute: false) : '');

        return array_values(array_filter([
            $terms !== '' ? ['label' => 'Terms and conditions', 'url' => $terms] : null,
            $privacy !== '' ? ['label' => 'Privacy policy', 'url' => $privacy] : null,
        ]));
    }

    /** The checkbox field for form schemas (sign-up, the accept page). */
    public static function field(): array
    {
        return [
            'id' => 'accept_terms',
            'label' => 'I have read and agree to the Terms and conditions and the Privacy policy',
            'type' => 'checkbox',
            'required' => true,
            'links' => self::links(),
        ];
    }

    public static function hasAccepted(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        return UserMeta::query()
            ->where('ref_parent', $user->getAuthIdentifier())
            ->where('meta_key', self::META_VERSION)
            ->where('meta_value', self::version())
            ->exists();
    }

    public static function record(Authenticatable $user): void
    {
        foreach ([self::META_VERSION => self::version(), self::META_ACCEPTED_AT => now()->toIso8601String()] as $key => $value) {
            UserMeta::query()->updateOrCreate(
                ['ref_parent' => $user->getAuthIdentifier(), 'meta_key' => $key],
                ['meta_value' => $value, 'status' => 'active']
            );
        }
    }
}
