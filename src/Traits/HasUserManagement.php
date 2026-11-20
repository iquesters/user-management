<?php

namespace Iquesters\UserManagement\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iquesters\UserManagement\Models\UserMeta;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

trait HasUserManagement
{
    use HasApiTokens, HasRoles;

    /** Serialize every Eloquent password writer with Python login/refresh.
     * Covers web change, recovery, admin edits and model updates without a
     * second identity store. Bulk SQL writers must use this unit of work.
     */
    public function save(array $options = [])
    {
        if (! $this->exists || ! $this->isDirty('password')) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            $connection = $this->getConnection();
            $connection->table($this->getTable())->where('id', $this->getKey())->lockForUpdate()->first();
            $this->setRememberToken(Str::random(60));
            $saved = parent::save($options);
            if ($saved) {
                $this->tokens()->delete();
                // Transitional hosts may precede the reviewed Track C schema.
                // New refresh APIs fail closed until it is installed.
                if ($connection->getSchemaBuilder()->hasTable('account_sessions')) {
                    $connection->table('account_sessions')->where('user_id', $this->getKey())
                        ->whereNull('revoked_at')->update(['revoked_at' => now()]);
                }
                if ($connection->getSchemaBuilder()->hasTable('account_security_audit')) {
                    $connection->table('account_security_audit')->insert([
                        'user_id' => $this->getKey(), 'event' => 'password_changed_legacy', 'session_id' => null, 'created_at' => now(),
                    ]);
                }
                if ($connection->getSchemaBuilder()->hasTable('sessions')) {
                    $connection->table('sessions')->where('user_id', $this->getKey())->delete();
                }
                Log::info('account_security.password_credentials_revoked', ['user_id' => $this->getKey()]);
            }

            return $saved;
        });
    }

    /**
     * users.uid is required: give users created outside this package
     * (factories, seeders, tinker, other packages) a ULID. Set on construction,
     * not in a `creating` listener, because seeders often run WithoutModelEvents.
     * Models loaded from the database replace it with their stored uid.
     */
    public function initializeHasUserManagement(): void
    {
        if (! array_key_exists('uid', $this->attributes)) {
            $this->attributes['uid'] = (string) Str::ulid();
        }
    }

    /**
     * User meta rows (user_metas). A User model that already defines
     * meta()/getMeta()/setMeta() keeps its own versions.
     */
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class, 'ref_parent');
    }

    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : $default;
    }

    public function setMeta(string $key, $value)
    {
        return $this->meta()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    /**
     * False for users who signed up with Google or an OTP: they were given a
     * random password they do not know (marked "password_generated").
     */
    public function hasUsablePassword(): bool
    {
        return ! empty($this->password) && $this->getMeta('password_generated') !== '1';
    }

    /** File name of the uploaded profile picture, or null (none, or removed). */
    public function profilePictureFile(): ?string
    {
        $file = $this->meta()
            ->where('meta_key', 'profile_picture')
            ->where('status', 'active')
            ->value('meta_value');

        return $file ? basename($file) : null;
    }

    /**
     * Absolute URL of the user's picture, usable on any page: the uploaded
     * picture, else the sign-in provider's picture (e.g. Google), else initials.
     */
    public function avatarUrl(): string
    {
        if ($file = $this->profilePictureFile()) {
            return route('profile.picture', ['filename' => $file]);
        }

        $providerPicture = $this->getMeta('logo');
        if (is_string($providerPicture) && str_starts_with($providerPicture, 'https://')) {
            return $providerPicture;
        }

        $initial = mb_strtoupper(mb_substr(trim((string) $this->name) ?: '?', 0, 1));

        return 'https://placehold.co/400x400/faf3e0/d72638/png?text='.rawurlencode($initial);
    }
}
