<?php

return [
    'root_cn'         => env('CA_ROOT_CN', 'EaaS Root CA'),
    'intermediate_cn' => env('CA_INTERMEDIATE_CN', 'EaaS Intermediate CA'),
    'org'             => env('CA_ORG', 'EaaS'),
    'country'         => env('CA_COUNTRY', 'TR'),

    'pkcs11' => [
        'module' => env('SOFTHSM_LIB'),
        'token' => env('SOFTHSM_TOKEN'),
        'pin' => env('SOFTHSM_PIN'),
    ]
];