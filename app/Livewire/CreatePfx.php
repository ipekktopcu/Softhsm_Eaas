<?php

namespace App\Livewire;

use App\Models\Ca;
use App\Models\CsrFile;
use App\Models\LeafKey;
use App\Models\PfxCert;
use App\Support\AuditLogger;
use App\Support\CertificateService;
use App\Support\OpenSslConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Livewire\Component;

class CreatePfx extends Component
{
    public string $keyLabel = '';
    public string $commonName = '';
    public string $organization = '';
    public string $country = '';

    public bool $showForm = false;
    public bool $showCsrForm = false;

    public string $error = '';
    public string $successMessage = '';

    public bool $showSignForm = false;
    public ?int $signingCsrId = null;
    public string $selectedCaId = '';
    public array $availableCas = [];

    public bool $showPfxModal = false;
    public string $pfxPassword = '';
    public string $selectedLabel = '';

    protected array $rules = [
        'keyLabel'     => 'required|string',
        'commonName'   => 'required|string|max:255',
        'organization' => 'nullable|string|max:255',
        'country'      => 'nullable|string|size:2',
    ];

    private function certService(): CertificateService
    {
        return app(CertificateService::class);
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
        $this->error = '';
        $this->successMessage = '';
    }

    public function toggleFormCsr(): void
    {
        $this->showCsrForm = ! $this->showCsrForm;
        $this->resetErrorBag();
        $this->error = '';
        $this->successMessage = '';
    }

    private function tmpDir(): string
    {
        $dir = storage_path('app/pfx_tmp');
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir;
    }

    private function pfxStoragePath(string $label): string
    {
        $dir = storage_path('app/pfx/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($label) . '.pfx';
    }

    public function generateKeyPair(): void
    {
        $this->validate(['keyLabel' => 'required|string']);
        $this->error = '';
        $this->successMessage = '';

        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (! $res) {
            $this->error = 'Failed to generate key pair: ' . openssl_error_string();
            return;
        }

        openssl_pkey_export($res, $privateKey);

        LeafKey::firstOrCreate(
            ['label' => $this->keyLabel, 'user_id' => auth()->id()],
            [
                'private_key'   => $privateKey,
                'serial_number' => Str::uuid()->toString(),
                'issued_at'     => now(),
                'expires_at'    => now()->addYear(),
            ]
        );

        AuditLogger::log('keypair.generated', "Key pair '{$this->keyLabel}' generated (database).");

        $this->successMessage = 'Key pair generated and stored encrypted in the database.';
        $this->reset(['keyLabel', 'commonName', 'organization', 'country', 'showForm']);
    }

    public function prepareCsrForm(string $label): void
    {
        $this->keyLabel = $label;
        $this->showCsrForm = true;
    }

    public function generateCsr(): void
    {
        $validated = $this->validate();
        $this->error = '';
        $this->successMessage = '';

        $leafKey = LeafKey::where('label', $this->keyLabel)->where('user_id', auth()->id())->first();
        if (! $leafKey || empty($leafKey->private_key)) {
            $this->error = 'Generate a key pair first.';
            return;
        }

        $csrFilePath = $this->csrStoragePath($this->keyLabel);
        $tmpKeyPath = $this->tmpDir() . '/' . uniqid('csr_key_', true) . '.key';
        file_put_contents($tmpKeyPath, $leafKey->private_key);

        $cmd = OpenSslConfig::prefix() . sprintf(
            'openssl req -new -key %s -out %s -subj %s -sha256 2>&1',
            escapeshellarg($tmpKeyPath),
            escapeshellarg($csrFilePath),
            escapeshellarg("/C={$this->country}/O={$this->organization}/CN={$this->commonName}")
        );
        $result = Process::run($cmd);
        @unlink($tmpKeyPath);

        if (! $result->successful()) {
            $this->error = 'Failed to create CSR: ' . $result->output();
            Log::error('PFX CSR generation: ' . $result->output());
            return;
        }

        @unlink($this->pfxStoragePath($this->keyLabel));

        CsrFile::create([
            'user_id'      => auth()->id(),
            'key_label'    => $this->keyLabel,
            'common_name'  => $this->commonName,
            'organization' => $this->organization,
            'country'      => strtoupper($this->country ?? ''),
            'file_path'    => $csrFilePath,
            'is_signed'    => false,
        ]);

        AuditLogger::log('csr.created', "CSR created for key '{$this->keyLabel}' (CN: {$this->commonName}).");

        $this->successMessage = "CSR created for key: {$this->keyLabel}";
        $this->showCsrForm = false;
        $this->reset(['keyLabel', 'commonName', 'organization', 'country']);
    }

    public function prepareSignForm(int $csrId): void
    {
        $this->error = '';
        $this->successMessage = '';

        $csr = CsrFile::where('id', $csrId)->where('user_id', auth()->id())->first();
        if (! $csr) {
            $this->error = 'CSR record not found.';
            return;
        }
        if ($csr->is_signed) {
            $this->error = 'This CSR is already signed.';
            return;
        }

        $this->availableCas = Ca::where('is_active', true)->where('label', '!=', 'Root CA')->get()->map(fn (Ca $ca) => [
            'id'    => $ca->id,
            'label' => $ca->label,
            'level' => $ca->level,
        ])->toArray();

        if (empty($this->availableCas)) {
            $this->error = 'No CA found. Run "php artisan ca:init" first.';
            return;
        }

        $this->signingCsrId = $csrId;
        $this->selectedCaId = '';
        $this->showSignForm = true;
    }

    public function cancelSignForm(): void
    {
        $this->showSignForm = false;
        $this->signingCsrId = null;
        $this->selectedCaId = '';
        $this->resetErrorBag();
    }

    public function confirmSignCsr(): void
    {
        $this->validate(['selectedCaId' => 'required|integer']);

        if (! $this->signingCsrId) {
            $this->error = 'No CSR selected for signing.';
            return;
        }

        $this->signCsr($this->signingCsrId, (int) $this->selectedCaId);
        $this->cancelSignForm();
    }

    public function signCsr(int $csrId, int $caId): void
{
    $this->error = '';
    $this->successMessage = '';

    $csr = CsrFile::where('id', $csrId)->where('user_id', auth()->id())->first();
    $ca = Ca::where('id', $caId)->where('is_active', true)->first();
    $leafKey = LeafKey::where('label', $csr?->key_label)->where('user_id', auth()->id())->latest()->first();

    if (! $csr || ! $ca) {
        $this->error = 'CSR or CA not found.';
        return;
    }
    if ($csr->is_signed) {
        $this->error = 'This CSR is already signed.';
        return;
    }
    if (! is_file($csr->file_path)) {
        $this->error = 'CSR file missing on disk.';
        return;
    }
    if (! $leafKey) {
        $this->error = 'Leaf key record not found.';
        return;
    }

    $certPath = $this->certStoragePath($csr->id, $csr->key_label);
    $serialPath = $this->serialStoragePath($ca->label);

    $isHsmKey = str_starts_with(trim($ca->private_key), 'pkcs11:');

    $tmpCertPath = $this->tmpDir() . '/' . uniqid('ca_cert_', true) . '.crt';
    file_put_contents($tmpCertPath, $ca->certificate);

    $tmpKeyPath = null;

    try {
        if ($isHsmKey) {
            preg_match('/pkcs11:[^\s\(]+/', $ca->private_key, $matches);
            if (empty($matches[0])) {
                throw new \RuntimeException('CA private_key PKCS#11 URI formatı okunamadı: ' . $ca->private_key);
            }
            $pkcs11Uri = rtrim($matches[0], ';');

            $pin = config('services.softhsm.pin');
            if (! str_contains($pkcs11Uri, 'pin-value=')) {
                $pkcs11Uri .= ';pin-value=' . $pin;
            }

            $cmd = OpenSslConfig::prefix() . sprintf(
                'openssl x509 -req -in %s -CA %s -CAkey %s -CAcreateserial -CAserial %s -days 365 -sha256 -out %s 2>&1',
                escapeshellarg($csr->file_path),
                escapeshellarg($tmpCertPath),
                escapeshellarg($pkcs11Uri),
                escapeshellarg($serialPath),
                escapeshellarg($certPath)
            );
        } else {
            $tmpKeyPath = $this->tmpDir() . '/' . uniqid('ca_key_', true) . '.key';
            file_put_contents($tmpKeyPath, $ca->private_key);

            $cmd = OpenSslConfig::prefix() . sprintf(
                'openssl x509 -req -in %s -CA %s -CAkey %s -CAcreateserial -CAserial %s -days 365 -sha256 -out %s 2>&1',
                escapeshellarg($csr->file_path),
                escapeshellarg($tmpCertPath),
                escapeshellarg($tmpKeyPath),
                escapeshellarg($serialPath),
                escapeshellarg($certPath)
            );
        }

        $result = Process::run($cmd);

        if (! $result->successful()) {
            throw new \RuntimeException('CA signing failed: ' . $result->output());
        }

        $certPem = (string) file_get_contents($certPath);
        $meta = $this->certService()->parse($certPem);
        $chainPem = $this->certService()->buildDbChain($certPem, $ca);

        $csr->update([
            'is_signed'       => true,
            'certificate_pem' => $certPem,
            'serial_number'   => $meta['serial_hex'],
            'issued_at'       => $meta['valid_from'],
            'expires_at'      => $meta['valid_until'],
        ]);

        $leafKey->update([
            'certificate'   => $certPem,
            'serial_number' => $meta['serial_hex'],
            'issued_at'     => $meta['valid_from'],
            'expires_at'    => $meta['valid_until'],
        ]);

        PfxCert::updateOrCreate(
            ['user_id' => auth()->id(), 'label' => $csr->key_label],
            [
                'key_id'               => $leafKey->id,
                'serial_number'        => $meta['serial_hex'],
                'issuer'               => $this->certService()->dnToString($meta['issuer']),
                'subject'              => $this->certService()->dnToString($meta['subject']),
                'signature_algorithm'  => $meta['signature_algorithm'],
                'public_key_algorithm' => $meta['public_key_algorithm'],
                'valid_from'           => $meta['valid_from'],
                'valid_until'          => $meta['valid_until'],
                'certificate_pem'      => $certPem,
                'chain_pem'            => $chainPem,
                'fingerprint_sha256'   => $meta['fingerprint'],
                'status'               => 'valid',
                'issuer_label'         => $ca->label,
            ]
        );

        @unlink($this->pfxStoragePath($csr->key_label));

        AuditLogger::log('csr.signed', "PFX CSR '{$csr->key_label}' signed by CA '{$ca->label}'.");

        $this->successMessage = "Certificate for '{$csr->key_label}' issued by {$ca->label}.";
    } catch (\Throwable $e) {
        $this->error = 'Signing failed: ' . $e->getMessage();
        Log::error('PFX CA signing: ' . $e->getMessage());
    } finally {
        @unlink($tmpCertPath);
        if ($tmpKeyPath) {
            @unlink($tmpKeyPath);
        }
    }
    }

    public function openPfxModal(string $label): void
    {
        $this->selectedLabel = $label;
        $this->pfxPassword = '';
        $this->showPfxModal = true;
        $this->resetErrorBag();
    }

    public function closePfxModal(): void
    {
        $this->showPfxModal = false;
        $this->selectedLabel = '';
        $this->pfxPassword = '';
    }

    public function generatePfx()
    {
        $this->validate(['pfxPassword' => 'required|min:4']);

        $leafKey = LeafKey::where('label', $this->selectedLabel)->where('user_id', auth()->id())->latest()->first();
        $pfxCert = PfxCert::where('label', $this->selectedLabel)->where('user_id', auth()->id())->latest()->first();

        if (! $leafKey || empty($leafKey->private_key)) {
            $this->error = 'Key record or private key not found in database.';
            $this->closePfxModal();
            return;
        }

        if (! $pfxCert || empty($pfxCert->certificate_pem)) {
            $this->error = 'No issued certificate for this key. Sign the CSR first.';
            $this->closePfxModal();
            return;
        }

        $tmpKey = $this->tmpDir() . '/' . uniqid('key_', true) . '.key';
        $tmpCert = $this->tmpDir() . '/' . uniqid('cert_', true) . '.crt';
        $tmpChain = $this->tmpDir() . '/' . uniqid('chain_', true) . '.crt';
        $pfxPath = $this->pfxStoragePath($this->selectedLabel);

        file_put_contents($tmpKey, $leafKey->private_key);
        file_put_contents($tmpCert, $pfxCert->certificate_pem);
        file_put_contents($tmpChain, $pfxCert->chain_pem ?: $pfxCert->certificate_pem);

        $cmd = sprintf(
            'openssl pkcs12 -export -in %s -inkey %s -certfile %s -passout pass:%s -name %s -out %s 2>&1',
            escapeshellarg($tmpCert),
            escapeshellarg($tmpKey),
            escapeshellarg($tmpChain),
            escapeshellarg($this->pfxPassword),
            escapeshellarg($this->selectedLabel),
            escapeshellarg($pfxPath)
        );
        $result = Process::run($cmd);

        @unlink($tmpKey);
        @unlink($tmpCert);
        @unlink($tmpChain);

        if (! $result->successful()) {
            $this->error = 'PFX generation error: ' . $result->output();
            Log::error('PFX generation: ' . $result->output());
            $this->closePfxModal();
            return;
        }

        $fileName = $this->selectedLabel;

        $this->closePfxModal();

        $this->successMessage = "PFX '{$this->selectedLabel}.pfx' created (cert + full chain). Downloading...";
        logger($this->selectedLabel);
        AuditLogger::log('pfx.created', "PFX bundle '{$this->selectedLabel}.pfx' created.");

        return response()->streamDownload(function () use ($pfxPath) {
            readfile($pfxPath);
        }, Str::slug($fileName) . '.pfx');
    }

    public function downloadCertificate(string $label)
    {
        $this->error = '';
        $this->successMessage = '';

        $cert = PfxCert::where('label', $label)->where('user_id', auth()->id())->latest()->first();
        if (! $cert) {
            $this->error = "No issued certificate for '{$label}'. Sign the CSR first.";
            return;
        }

        AuditLogger::log('certificate.downloaded', "Certificate '{$label}.crt' downloaded (full chain).");

        return response()->streamDownload(function () use ($cert) {
            echo $cert->chain_pem ?: $cert->certificate_pem;
        }, Str::slug($label) . '.crt', ['Content-Type' => 'application/x-pem-file']);
    }

    public function render()
    {
        $leafKeys = LeafKey::where('user_id', auth()->id())->latest()->get();
        $leafKeyLabels = $leafKeys->pluck('label')->all();

        $csrFiles = CsrFile::where('user_id', auth()->id())
            ->whereIn('key_label', $leafKeyLabels)
            ->latest()
            ->get();

        $pfxCerts = PfxCert::where('user_id', auth()->id())->latest()->get()->keyBy('label');

        $pfxDir = storage_path('app/pfx/' . auth()->id());
        $pfxLabels = is_dir($pfxDir)
            ? collect(scandir($pfxDir))
                ->filter(fn ($file) => str_ends_with($file, '.pfx'))
                ->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))
                ->values()
            : collect();

        $stats = [
            'keys'    => $leafKeys->count(),
            'signed'  => $csrFiles->where('is_signed', true)->count(),
            'pending' => $csrFiles->where('is_signed', false)->count(),
            'pfx'     => $pfxLabels->count(),
        ];

        return view('livewire.create-pfx', [
            'csrFiles'  => $csrFiles,
            'leafKeys'  => $leafKeys,
            'pfxCerts'  => $pfxCerts,
            'pfxLabels' => $pfxLabels,
            'cas'       => Ca::where('is_active', true)->get(),
            'stats'     => $stats,
        ])->layout('layouts.app');
    }

    private function csrStoragePath(string $label): string
    {
        $dir = storage_path('app/pfx_csr/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($label) . '_' . Str::random(8) . '.csr';
    }

    private function certStoragePath(int $csrId, string $label): string
    {
        $dir = storage_path('app/pfx_cert/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . $csrId . '_' . Str::slug($label) . '.crt';
    }

    private function serialStoragePath(string $caLabel): string
    {
        $dir = storage_path('app/serial/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($caLabel) . '.srl';
    }
}