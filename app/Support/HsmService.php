<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class HsmService
{
    private string $lib;
    private string $token;
    private string $pin;

    public function __construct()
    {
        $this->lib   = config('softhsm.lib');
        $this->token = config('softhsm.token');
        $this->pin   = config('softhsm.pin');
    }

    public function listObjects(): string
    {
        return $this->run(['--list-objects'])['output'];
    }

    public function listCertificates(): array
    {
        $output = $this->run(['--list-objects', '--type', 'cert'])['output'];
        preg_match_all('/label:\s*(.*)/', $output, $matches);

        $labels = [];
        foreach (($matches[1] ?? []) as $label) {
            $labels[] = trim($label);
        }

        return array_values(array_unique($labels));
    }

    public function listCaCertificates(): array
    {
        $cas = [];
        foreach ($this->listCertificates() as $label) {
            $pem = $this->readCertificate($label);
            if ($pem === null) continue;

            $info = openssl_x509_parse($pem);
            $bc = $info['extensions']['basicConstraints'] ?? '';
            if (stripos($bc, 'CA:TRUE') !== false) {
                $cas[] = $label;
            }
        }
        return $cas;
    }

    public function generateKeyPair(string $label, int $bits = 2048): void
    {
        $cmd = sprintf(
            'p11keygen -l %s -t %s -p %s -k rsa -b %d -i %s sign verify',
            escapeshellarg($this->lib),
            escapeshellarg($this->token),
            escapeshellarg($this->pin),
            $bits,
            escapeshellarg($label)
        );
        $result = Process::run($cmd);
        if (! $result->successful()) {
            throw new RuntimeException('p11keygen failed: ' . $result->errorOutput());
        }
    }

    public function hasPrivateKey(string $label): bool
    {
        $output = $this->run(['--list-objects', '--type', 'privkey'])['output'];
       preg_match_all('/label:\s*(.*)/', $output, $matches);
        foreach (($matches[1] ?? []) as $found) {
        if (trim($found) === $label) {
            return true;
        }
    }
    return false;

    }

    public function deleteKeyPair(string $label): void
    {
        try {
            $this->run(['--delete-object', '--type', 'privkey', '--label', $label]);
        } catch (RuntimeException $e) {
            Log::debug("No private key to delete for label '{$label}': " . $e->getMessage());
        }

        try {
            $this->run(['--delete-object', '--type', 'pubkey', '--label', $label]);
        } catch (RuntimeException $e) {
            Log::debug("No public key to delete for label '{$label}': " . $e->getMessage());
        }
    }

    public function deleteCertificate(string $label): void
    {
        try {
            $this->run(['--delete-object', '--type', 'cert', '--label', $label]);
        } catch (RuntimeException $e) {
            Log::debug("No certificate to delete for label '{$label}': " . $e->getMessage());
        }
    }

    public function createCsr(string $label, string $dn, string $outputPath): void
    {
        $cmd = sprintf(
            'p11req -l %s -t %s -p %s -i %s -d %s -H sha256 -o %s',
            escapeshellarg($this->lib),
            escapeshellarg($this->token),
            escapeshellarg($this->pin),
            escapeshellarg($label),
            escapeshellarg($dn),
            escapeshellarg($outputPath)
        );
        $result = Process::run($cmd);
        if (! $result->successful()) {
            throw new RuntimeException('p11req failed: ' . $result->errorOutput());
        }
    }

    public function readCertificate(string $label): ?string
    {
        $output = $this->run(['--read-object', '--type', 'cert', '--label', $label])['output'];

        $pem = $this->extractPem($output);
        if ($pem !== null) {
            return $pem;
        }

        $der = trim($output);
        if ($der === '' || ! str_starts_with($der, "\x30")) {
            return null;
        }

        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END CERTIFICATE-----\n";
    }

    public function writeCertificate(string $label, string $pem): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cert_');
        file_put_contents($tmp, $pem);
        try {
            $id = $this->keyId($label) ?? $this->derivedId($label);
            $this->run(['--write-object', $tmp, '--type', 'cert', '--label', $label, '--id', $id]);
        } finally {
            @unlink($tmp);
        }
    }

    public function signCsrWithHsmKey(string $csrPath, string $issuerLabel, string $certPath, string $serialPath, int $days = 365): void
    {
        $keyUri = sprintf(
            
            'pkcs11:token=%s;object=%s;type=private;pin-value=%s',
            rawurlencode($this->token),
            rawurlencode($issuerLabel),
            rawurlencode($this->pin)
        );
        $caUri = sprintf(
            'pkcs11:token=%s;object=%s;type=cert;pin-value=%s',
            rawurlencode($this->token),
            rawurlencode($issuerLabel),
            rawurlencode($this->pin)
        );
        $cmd = OpenSslConfig::prefix() . sprintf(
            'openssl x509 -req -in %s -CA %s -CAkey %s -provider default -provider pkcs11 -CAcreateserial -CAserial %s -days %d -sha256 -out %s 2>&1',
            escapeshellarg($csrPath),
            escapeshellarg($caUri),
            escapeshellarg($keyUri),
            escapeshellarg($serialPath),
            $days,
            escapeshellarg($certPath)
        );
        $result = Process::run($cmd);
        if (! $result->successful()) {
            throw new RuntimeException('HSM signing failed: ' . $result->output());
        }
    }

    private function keyId(string $label): ?string
    {
        $output = $this->run(['--list-objects', '--type', 'privkey'])['output'];

        foreach (preg_split('/\n(?=Private Key Object)/', $output) as $block) {
            if (! preg_match('/label:\s*(.*)/', $block, $labelMatch)) {
                continue;
            }
            if (trim($labelMatch[1]) !== $label) {
                continue;
            }
            if (preg_match('/ID:\s*([0-9a-fA-F]+)/', $block, $idMatch)) {
                return $idMatch[1];
            }
        }

        return null;
    }

    private function derivedId(string $label): string
    {
        return substr(hash('sha256', $label), 0, 20);
    }

    private function extractPem(string $raw): ?string
    {
        $start = strpos($raw, '-----BEGIN CERTIFICATE-----');
        if ($start === false) return null;
        $end = strpos($raw, '-----END CERTIFICATE-----', $start);
        if ($end === false) return null;
        return substr($raw, $start, $end - $start + strlen('-----END CERTIFICATE-----')) . "\n";
    }

    private function run(array $args): array
    {
        $cmd = 'pkcs11-tool --module ' . escapeshellarg($this->lib)
            . ' --login --pin ' . escapeshellarg($this->pin);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }
        $result = Process::run($cmd);
        if (! $result->successful()) {
            throw new RuntimeException('pkcs11-tool failed: ' . $result->errorOutput());
        }
        return ['output' => $result->output()];
    }
}