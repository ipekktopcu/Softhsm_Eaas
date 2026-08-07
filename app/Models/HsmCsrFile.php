<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsmCsrFile extends Model
{
    protected $fillable = [
        'user_id',
        'key_label',
        'common_name',
        'organization',
        'country',
        'file_path',
        'is_signed',
        'certificate_pem',
        'serial_number',
        'issued_at',
        'expires_at',
        'issuer_label',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
