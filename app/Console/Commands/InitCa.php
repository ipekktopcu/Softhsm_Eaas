<?php

namespace App\Console\Commands;

use App\Models\Ca;
use App\Support\CertificateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class InitCa extends Command
{
    protected $signature = 'ca:init {--force : Re-seed even if a root CA already exists}';

    protected $description = 'Seed a Root and Intermediate CA into the cas table if empty';

    private string $module;
    private string $token;
    private string $pin;

    public function handle(): int
    {
        if (! $this->option('force') && Ca::where('level', 'root')->exists()) {
            $this->info('CAs already seeded. Use --force to re-create.');
            return self::SUCCESS;
        }

        $this->module = (string) config('ca.pkcs11.module');
        $this->token = (string) config('ca.pkcs11.token');
        $this->pin = (string) config('ca.pkcs11.pin');

        if($this->pin === ''){
            $this->error('SOFTHSM_PIN is not set');
            return self::FAILURE;
        }

        $service = app(CertificateService::class);

        $tmp = storage_path('app/ca_tmp');
        if (! is_dir($tmp)) mkdir($tmp, 0700, true);

        $country = config('ca.country');
        $org = config('ca.org');
        $rootCn = config('ca.root_cn');
        $intCn = config('ca.intermediate_cn');

        $rootCert = $tmp . '/root.crt';
        $intCsr = $tmp . '/int.csr';
        $intCert = $tmp . '/int.crt';
        $serial = $tmp . '/root.srl';

        $rootLabel = 'root-ca-key' . now()->format('YmdHis');
        $intLabel = 'int-ca-key' . now()->format('YmdHis');

        $this->hsmKeygen($rootLabel, '01');
        $rootKeyUri = $this->keyUri($rootLabel);

       $this->execWithProvider(sprintf(
        'openssl req -x509 -new -sha256 -days 3650 -out %s -subj %s -key %s',
        escapeshellarg($rootCert),
        escapeshellarg("/C={$country}/O={$org}/CN={$rootCn}"),
        escapeshellarg($rootKeyUri)
        ));


        $this->hsmKeygen($intLabel, '02');
        $intKeyUri = $this->keyUri($intLabel);

        $this->execWithProvider(sprintf(
            'openssl req -new -out %s -subj %s -key %s',
            escapeshellarg($intCsr),
            escapeshellarg("/C={$country}/O={$org}/CN={$intCn}"),
            escapeshellarg($intKeyUri)
        ));

        $this->execWithProvider(sprintf(
            'openssl x509 -req -in -CA %s -CAkey %s -CAcreateserial %s -days 1825 -sha256 -out %s',
            escapeshellarg($intCsr),
            escapeshellarg($rootCert),
            escapeshellarg($rootKeyUri),
            escapeshellarg($serial),
            escapeshellarg($intCert)
        ));

        $this->storeCa($service, 'Root CA', 'root', $rootCn, $rootKeyUri, $rootCert, null);
        $this->storeCa($service, 'Intermediate CA', 'intermediate', $intCn, $intKeyUri, $intCert, 'Root CA');

        foreach ([$rootCert, $intCsr, $intCert, $serial] as $f) {
            @unlink($f);
        }

        $this->info('Root and Intermediate CA seeded into database.');
        return self::SUCCESS;
    }

    private function hsmKeygen(string $label, string $id)
    {
        $this->exec(sprintf(
            'pkcs11-tool --module %s --token-label %s --login --pin %s --keypairgen --key-type rsa:4096 --label %s --id %s',
            escapeshellarg(ca.pkcs11.module),
            escapeshellarg(ca.pkcs11-token),
            escapeshellarg(ca.pkcs11.pin),
            escapeshellarg($label),
            escapeshellarg($id)
        ));
    }

    private function keyUri(string $label): string
    {
        return sprintf(
            'pkcs11:token=%s;object=%s;type=private',
            rawurlencode($this->token),
            rawurlencode($label)
        );
    }

    private function execWithProvider(string $opensslCmd): void
    {
       $cmf = sprintf(
        'openssl %s -provider pkcs11 -provider default',
        substr($opensslCmd, strlen('openssl '))
        );

        $result = Process::env([
            'PKCS11_MODULE_PATH' => $this->module,
            'PKCS11_PIN' => $this->pin,
        ])->run($cmd . ' 2>&1');

        if (! $result->successful()) {
            throw new RuntimeException('Command failed: ' . $cmd . PHP_EOL . $result->output());
        }
    }

    private function storeCa(
        CertificateService $service, 
        string $label, 
        string $level, 
        string $cn, 
        string $keyUri, 
        string $certPath, 
        ?string $issuerLabel): void
    {
        $pem = (string) file_get_contents($certPath);
        $meta = $service->parse($pem);

        Ca::create([
            'label'             => $label,
            'level'             => $level,
            'common_name'       => $cn,
            'organization'      => config('ca.org'),
            'country'           => config('ca.country'),
            'private_key'       => $keyUri,
            'key_storage'       => 'pkcs11',
            'certificate'       => $pem,
            'serial_number'     => $meta['serial_hex'],
            'issuer_label'      => $issuerLabel,
            'valid_from'        => $meta['valid_from'],
            'valid_until'       => $meta['valid_until'],
            'fingerprint_sha256' => $meta['fingerprint'],
            'is_active'         => true,
        ]);
    }

    private function exec(string $cmd): void
    {
        $result = Process::run($cmd . ' 2>&1');
        if (! $result->successful()) {
            throw new RuntimeException('Command failed: ' . $cmd . PHP_EOL . $result->output());
        }
    }
}