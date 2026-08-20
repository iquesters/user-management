<?php

namespace Iquesters\UserManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtpAttempt extends Model
{
    use HasFactory;

    protected $table = 'otp_attempts';

    protected $fillable = [
        'identifier_type',
        'identifier_value',
        'delivery_channel',
        'code_hash',
        'expires_at',
        'attempt_count',
        'max_attempts',
        'consumed_at',
        'last_sent_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'max_attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
        'created_by' => 0,
        'updated_by' => 0,
    ];

    public function metas(): HasMany
    {
        return $this->hasMany(OtpAttemptMeta::class, 'ref_parent');
    }

    public function getMetaValue(string $key): ?string
    {
        return $this->metas()
            ->where('meta_key', $key)
            ->where('status', 'active')
            ->value('meta_value');
    }

    public function setMetaValue(string $key, $value): OtpAttemptMeta
    {
        return $this->metas()->updateOrCreate(
            ['meta_key' => $key],
            [
                'meta_value' => (string) $value,
                'status' => 'active',
                'created_by' => $this->created_by ?? 0,
                'updated_by' => $this->updated_by ?? 0,
            ]
        );
    }
}
