<?php

namespace Iquesters\UserManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpAttemptMeta extends Model
{
    use HasFactory;

    protected $table = 'otp_attempt_metas';

    protected $fillable = [
        'ref_parent',
        'meta_key',
        'meta_value',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ref_parent' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
        'created_by' => 0,
        'updated_by' => 0,
    ];

    public function otpAttempt(): BelongsTo
    {
        return $this->belongsTo(OtpAttempt::class, 'ref_parent');
    }
}
