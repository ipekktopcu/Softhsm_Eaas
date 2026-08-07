<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Audit logs are immutable by design: they must never be updated.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Audit logs cannot be updated.');
    }

    /**
     * Audit logs are immutable by design: they must never be deleted.
     */
    public function delete(): ?bool
    {
        throw new LogicException('Audit logs cannot be deleted.');
    }
}
