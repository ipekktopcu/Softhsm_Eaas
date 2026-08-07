<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsmCert extends Model
{
    protected $table = 'hsm_certs';

    protected $fillable = [
        'user_id',
        'key_id',
        'label',
        'serial_number',
        'issuer',
        'subject',
        'signature_algorithm',
        'public_key_algorithm',
        'valid_from',
        'valid_until',
        'certificate_pem',
        'chain_pem',
        'fingerprint_sha256',
        'status',
        'issuer_label',
    ];

    protected $casts = [
        'valid_from'  => 'datetime',
        'valid_until' => 'datetime',
    ];
}