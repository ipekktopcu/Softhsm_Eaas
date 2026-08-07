<?php

namespace App\Support;

final class OpenSslConfig
{
    public static function prefix(): string
    {
        $path = config('services.softhsm.openssl_conf');
        if (! is_string($path) || $path === '' || ! is_file($path)) {
            $path = storage_path('app/openssl-pkcs11.cnf');
        }

        if (! is_file($path)) {
            return '';
        }

        return 'OPENSSL_CONF=' . escapeshellarg($path) . ' ';
    }
}
