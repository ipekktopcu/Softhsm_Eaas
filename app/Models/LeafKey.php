<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeafKey extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'common_name',
        'organization',
        'country',
        'private_key',
        'certificate',
        'serial_number',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'certificate' => 'encrypted',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }
}
