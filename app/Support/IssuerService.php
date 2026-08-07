<?php

namespace App\Support;

use App\Models\Ca;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class IssuerService {

     public function __construct(private HsmService $hsm)
    {
    }


    public function createKeyPair(string $keyLabel, int $bits = 4096): void
    {
        if ($this->hsm->hasPrivateKey($keyLabel)) {
            throw new RuntimeException("Private key with label '$keyLabel' already exists.");
        }

        $this->hsm->generateKeyPair($keyLabel, $bits);
    }
   

    public function createCsr(
        string $keyLabel,
        string $commonName,
        ?string $organization,
        ?string $country,
        string $outputPath
    ): void {

        $keyUri = sprintf(
            'pkcs11:token=%s;object=%s;type=private;pin-value=%s',
            rawurlencode(config('softhsm.token')),
            rawurlencode($keyLabel),
            rawurlencode(config('softhsm.pin'))
        );

        $subject = sprintf(
            '/CN=%s%s%s',
            $commonName,
            $organization ? '/O=' . $organization : '',
            $country ? '/C=' . $country : ''
        );

        $cmd = OpenSslConfig::prefix() . sprintf(
            'openssl req -new -provider default -provider pkcs11 -key %s -out %s -subj %s -sha256 ' . '-addext %s -addext %s 2>&1',
            escapeshellarg($keyUri),
            escapeshellarg($outputPath),
            escapeshellarg($subject),
            escapeshellarg('basicConstraints=CA:TRUE'),
            escapeshellarg('keyUsage = critical, digitalSignature, cRLSign, keyCertSign')
        );   

        $result = Process::run($cmd);
        if (!$result->successful()) {
            throw new RuntimeException("Failed to create CSR: " . $result->output());
        }
    }

    public function signWithRoot(
        string $csrPath,
        string $signerLabel,
        string $certPath,
        string $serialPath,
        int $days = 365
    ): void {
        if (!$this->hsm->hasPrivateKey($signerLabel)) {
            throw new RuntimeException("Signer private key with label '$signerLabel' does not exist.");
        }

        $keyUri = sprintf(
            'pkcs11:token=%s;object=%s;type=private;pin-value=%s',
            rawurlencode(config('softhsm.token')),
            rawurlencode($signerLabel),
            rawurlencode(config('softhsm.pin'))
        );
        $caUri = sprintf(
            'pkcs11:token=%s;object=%s;type=cert;pin-value=%s',
            rawurlencode(config('softhsm.token')),
            rawurlencode($signerLabel),
            rawurlencode(config('softhsm.pin'))
        );

        $cmd = OpenSslConfig::prefix() . sprintf(
            'openssl x509 -req -in %s -CA %s -CAkey %s -provider default -provider pkcs11 -CAcreateserial -CAserial %s -days %d -sha256 -copy_extensions copy -out %s 2>&1',
            escapeshellarg($csrPath),
            escapeshellarg($caUri),
            escapeshellarg($keyUri),
            escapeshellarg($serialPath),
            $days,
            escapeshellarg($certPath)
        );

        $result = Process::run($cmd);
        if (!$result->successful()) {
            throw new RuntimeException("Failed to sign CSR: " . $result->output());
        }
    
    }
    
    public function saveIssuer(
        string $keyLabel,
        string $commonName,
        ?string $organization,
        ?string $country,
        string $signerLabel,
        string $certPem,
        array $meta
    ): Ca {
        try {
            $this->hsm->writeCertificate($keyLabel, $certPem);
        } catch (\Throwable $e) {
            Log::warning("Could not import issuer certificate '{$keyLabel}' back into HSM: " . $e->getMessage());
        }

        $keyUriRef = sprintf(
            'pkcs11:token=%s;object=%s;type=private (non-extractable, held in HSM)',
            config('softhsm.token'),
            rawurlencode($keyLabel)
        );
    
    return Ca::create([
            'label' => $keyLabel,
            'level' => 'intermediate',
            'common_name' => $commonName,
            'organization' => $organization,
            'country' => $country ? strtoupper($country) : null,
            'private_key' => $keyUriRef,
            'key_storage' => 'pkcs11',
            'certificate' => $certPem,
            'serial_number' => $meta['serial_hex'] ?? null,
            'issuer_label' => $signerLabel,
            'valid_from' => $meta['valid_from'] ?? null,
            'valid_until' => $meta['valid_until'] ?? null,
            'fingerprint_sha256' => $meta['fingerprint'] ?? null,
            'is_active' => true,
        ]);

    }
}