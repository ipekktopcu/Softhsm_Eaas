<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsrFile extends Model
{
    use HasFactory;

    // Mass assignment (toplu veri ekleme) için izin verilen alanlar
    protected $fillable = [
        'user_id',
        'key_label',
        'common_name',
        'organization',
        'country',
        'file_path',
        'is_signed',
        'private_key_pem',
        'certificate_pem',
        'serial_number',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'private_key_pem' => 'encrypted',
        'is_signed' => 'boolean',
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