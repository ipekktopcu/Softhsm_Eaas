<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ca extends Model
{
    protected $fillable = [
        'label',
        'level',
        'common_name',
        'organization',
        'country',
        'private_key',
        'key_storage',
        'certificate',
        'serial_number',
        'issuer_label',
        'valid_from',
        'valid_until',
        'fingerprint_sha256',
        'is_active',
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'certificate' => 'encrypted',
        'valid_from'  => 'datetime',
        'valid_until' => 'datetime',
        'is_active'   => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'issuer_label', 'label');
    }

     public function isHsmBacked(): bool
    {
        return $this->key_storage === 'pkcs11';
    }
}