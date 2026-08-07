<?php
return [
    'lib' => env('SOFTHSM_LIB', 'usr/lib64/softhsm/libsofthsm.so'),
    'token' => env('SOFTHSM_TOKEN_LABEL', 'eaas-token'),
    'pin' => env('SOFTHSM_PIN', '1234'),
];