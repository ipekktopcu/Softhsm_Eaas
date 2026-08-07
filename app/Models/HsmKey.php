<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsmKey extends Model
{
        protected $fillable = [
               'user_id',
               'label',
               'hsm_id',
               'common_name',
               'csr_path',
               'cert_path',
               'cert_issued_at',
        ];

        protected $casts = [
        'cert_issued_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}