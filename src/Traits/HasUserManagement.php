<?php

namespace Iquesters\UserManagement\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Iquesters\UserManagement\Models\UserMeta;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

trait HasUserManagement
{
    use HasApiTokens, HasRoles;

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
