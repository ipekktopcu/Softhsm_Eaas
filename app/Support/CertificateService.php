<?php

namespace App\Support;

use App\Models\Ca;
use Carbon\Carbon;
use RuntimeException;

class CertificateService
{
    public function __construct(private HsmService $hsm)
    {
    }

    public function parse(string $pem): array
    {
        $data = openssl_x509_parse($pem);
        if ($data === false) {
            throw new RuntimeException('Could not parse certificate PEM.');
        }

        $bc = $data['extensions']['basicConstraints'] ?? '';

        return [
            'subject'              => $data['subject'] ?? null,
            'issuer'               => $data['issuer'] ?? null,
            'serial_hex'           => $data['serialNumberHex'] ?? null,
            'valid_from'           => isset($data['validFrom_time_t'])
                ? Carbon::createFromTimestamp($data['validFrom_time_t'])
                : null,
            'valid_until'          => isset($data['validTo_time_t'])
                ? Carbon::createFromTimestamp($data['validTo_time_t'])
                : null,
            'signature_algorithm'  => $data['signatureTypeLN'] ?? null,
            'public_key_algorithm' => 'RSA ' . ($data['bits'] ?? ''),
            'fingerprint'          => str_replace(':', '', strtolower(openssl_x509_fingerprint($pem, 'sha256'))),
            'is_ca'                => stripos($bc, 'CA:TRUE') !== false,
            'is_self_signed'       => $this->normalizeDn($data['subject'] ?? null) === $this->normalizeDn($data['issuer'] ?? null),
        ];
    }

    public function buildHsmChain(string $leafPem): string
    {
        $chain = [trim($leafPem) . "\n"];
        $issuerDn = $this->parse($leafPem)['issuer'];
        $seen = [];

        for ($i = 0; $i < 5; $i++) {
            $cert = $this->findHsmCertBySubject($issuerDn);
            if ($cert === null) break;
            if (isset($seen[$cert['label']])) break;
            $seen[$cert['label']] = true;

            $chain[] = $cert['pem'];
            $meta = $this->parse($cert['pem']);
            if ($meta['is_self_signed']) break;
            $issuerDn = $meta['issuer'];
        }

        return implode('', $chain);
    }

    public function buildDbChain(string $leafPem, Ca $issuer): string
    {
        $chain = [trim($leafPem) . "\n"];
        $current = $issuer;
        $seen = [];

        while ($current !== null && ! isset($seen[$current->id])) {
            $seen[$current->id] = true;
            $chain[] = trim($current->certificate) . "\n";
            if ($current->level === 'root') break;
            $current = Ca::where('label', $current->issuer_label)->first();
        }

        return implode('', $chain);
    }

    public function dnToString(mixed $dn): string
    {
        if (! is_array($dn)) {
            return (string) $dn;
        }

        return implode(', ', array_map(
            fn ($key, $value) => $key . '=' . (is_array($value) ? implode('+', $value) : $value),
            array_keys($dn),
            array_values($dn)
        ));
    }

    public function normalizeDn(mixed $dn): string
    {
        if (is_array($dn)) {
            ksort($dn);
            $str = '';
            foreach ($dn as $k => $v) {
                $str .= '/' . strtoupper($k) . '=' . $v;
            }
            return $str;
        }

        $dn = trim((string) $dn);
        $dn = preg_replace('/^DN:\s*/i', '', $dn);
        $pairs = [];

        $parts = str_contains($dn, '/') ? explode('/', $dn) : explode(',', $dn);
        foreach ($parts as $part) {
            $part = trim($part);
            if (! str_contains($part, '=')) continue;
            [$k, $v] = explode('=', $part, 2);
            $pairs[strtoupper(trim($k))] = trim($v);
        }
        ksort($pairs);

        $str = '';
        foreach ($pairs as $k => $v) {
            $str .= '/' . $k . '=' . $v;
        }
        return $str;
    }

    private function findHsmCertBySubject(mixed $subjectDn): ?array
    {
        if ($subjectDn === null) return null;
        $normalized = $this->normalizeDn($subjectDn);

        foreach ($this->hsmCertificates() as $cert) {
            if ($this->normalizeDn($cert['subject']) === $normalized) {
                return $cert;
            }
        }
        return null;
    }

    private ?array $hsmCertCache = null;

    private function hsmCertificates(): array
    {
        if ($this->hsmCertCache !== null) return $this->hsmCertCache;

        $certs = [];
        foreach ($this->hsm->listCertificates() as $label) {
            $pem = $this->hsm->readCertificate($label);
            if ($pem === null) continue;
            try {
                $meta = $this->parse($pem);
            } catch (\Throwable) {
                continue;
            }
            $certs[] = ['label' => $label, 'pem' => $pem, 'subject' => $meta['subject']];
        }

        return $this->hsmCertCache = $certs;
    }
}