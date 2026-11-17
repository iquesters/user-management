<?php

namespace Iquesters\UserManagement\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Iquesters\UserManagement\Models\UserMeta;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

trait HasUserManagement
{
    use HasApiTokens, HasRoles;

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
}
