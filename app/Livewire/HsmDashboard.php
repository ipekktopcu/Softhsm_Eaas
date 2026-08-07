<?php

namespace App\Livewire;

use App\Models\HsmCert;
use App\Models\HsmCsrFile;
use App\Models\HsmKey;
use App\Support\AuditLogger;
use App\Support\CertificateService;
use App\Support\HsmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class HsmDashboard extends Component
{
    public string $error = '';
    public string $successMessage = '';

    public bool $showForm = false;
    public bool $showCsrForm = false;

    public string $keyLabel = '';
    public string $commonName = '';
    public string $organization = '';
    public string $country = '';

    public bool $showSignForm = false;
    public ?int $signingCsrId = null;
    public string $selectedIssuer = '';
    public array $hsmCAs = [];

    protected array $rules = [
        'keyLabel' => 'required|string',
    ];

    private function hsm(): HsmService
    {
        return app(HsmService::class);
    }

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

    public function prepareCsrForm(string $label): void
    {
        $this->keyLabel = $label;
        $this->showCsrForm = true;
    }

    public function generateKeyPair(): void
    {
        $this->validate();

        try {
            $this->hsm()->generateKeyPair($this->keyLabel);
        } catch (\Throwable $e) {
            $this->error = 'Key generation failed: ' . $e->getMessage();
            Log::error('HSM key generation: ' . $e->getMessage());
            return;
        }

        HsmKey::firstOrCreate(['label' => $this->keyLabel, 'user_id' => auth()->id()]);

        AuditLogger::log('keypair.generated', "Key pair '{$this->keyLabel}' generated on HSM.");

        $this->successMessage = "Key pair '{$this->keyLabel}' generated on SoftHSM.";
        $this->reset(['keyLabel', 'showForm']);
    }

    public function generateCsr(): void
    {
        $validated = $this->validate([
            'keyLabel'     => 'required|string',
            'commonName'   => 'required|string',
            'organization' => 'nullable|string',
            'country'      => 'nullable|string|size:2',
        ]);

        try {
            if (! $this->hsm()->hasPrivateKey($validated['keyLabel'])) {
                $this->error = "No private key '{$validated['keyLabel']}' found in SoftHSM.";
                return;
            }

            $csrPath = $this->csrStoragePath($validated['keyLabel']);

            $this->hsm()->createCsr(
                $validated['keyLabel'],
                sprintf(
                    '/CN=%s/O=%s/C=%s',
                    $validated['commonName'],
                    $validated['organization'] ?? '',
                    strtoupper($validated['country'] ?? '')
                ),
                $csrPath
            );
        } catch (\Throwable $e) {
            $this->error = 'CSR generation failed: ' . $e->getMessage();
            Log::error('HSM CSR generation: ' . $e->getMessage());
            return;
        }

        HsmCsrFile::create([
            'user_id'      => auth()->id(),
            'key_label'    => $validated['keyLabel'],
            'common_name'  => $validated['commonName'],
            'organization' => $validated['organization'] ?? null,
            'country'      => strtoupper($validated['country'] ?? ''),
            'file_path'    => $csrPath,
            'is_signed'    => false,
        ]);

        AuditLogger::log('csr.created', "HSM CSR created for key '{$validated['keyLabel']}' (CN: {$validated['commonName']}).");

        $this->successMessage = 'CSR generated for key ' . $validated['keyLabel'] . '.';
        $this->reset(['keyLabel', 'commonName', 'organization', 'country', 'showCsrForm']);
    }

    public function prepareSignForm(int $csrId): void
    {

        $this->error = '';
        $this->successMessage = '';

        $csr = HsmCsrFile::where('id', $csrId)->where('user_id', auth()->id())->first();
        if (! $csr) {
            $this->error = 'CSR record not found.';
            return;
        }
        if ($csr->is_signed) {
            $this->error = 'This CSR is already signed.';
            return;
        }

        $this->hsmCAs = $this->hsm()->listCaCertificates();
        if (empty($this->hsmCAs)) {
            $this->error = 'No CA certificates found in SoftHSM.';
            return;
        }

        $this->signingCsrId = $csrId;
        $this->selectedIssuer = '';
        $this->showSignForm = true;
    }

    public function cancelSignForm(): void
    {
        $this->showSignForm = false;
        $this->signingCsrId = null;
        $this->selectedIssuer = '';
        $this->resetErrorBag();
    }

    public function confirmSignCsr(): void
    {
        $this->validate(['selectedIssuer' => 'required|string']);

        if (! $this->signingCsrId) {
            $this->error = 'No CSR selected for signing.';
            return;
        }

        $this->signCsr($this->signingCsrId, $this->selectedIssuer);
        $this->cancelSignForm();
    }

    public function signCsr(int $csrId, string $issuerLabel): void
    {
        $this->error = '';
        $this->successMessage = '';

        $csr = HsmCsrFile::where('id', $csrId)->where('user_id', auth()->id())->first();
        if (! $csr) {
            $this->error = 'CSR record not found.';
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
        if (! in_array($issuerLabel, $this->hsm()->listCaCertificates(), true)) {
            $this->error = "Issuer '{$issuerLabel}' not found in SoftHSM.";
            return;
        }

        $certPath = $this->certStoragePath($csrId, $csr->key_label);
        $serialPath = $this->serialStoragePath($issuerLabel);

        try {
            $this->hsm()->signCsrWithHsmKey($csr->file_path, $issuerLabel, $certPath, $serialPath);
        } catch (\Throwable $e) {
            $this->error = 'Signing failed: ' . $e->getMessage();
            Log::error('HSM signing: ' . $e->getMessage());
            return;
        }

        $certPem = (string) file_get_contents($certPath);
        $meta = $this->certService()->parse($certPem);
        $chainPem = $this->certService()->buildHsmChain($certPem);

        try {
            $this->hsm()->writeCertificate($csr->key_label, $certPem);
        } catch (\Throwable $e) {
            Log::warning('Could not import issued certificate back into HSM: ' . $e->getMessage());
        }

        $hsmKey = HsmKey::firstOrCreate(
            ['label' => $csr->key_label, 'user_id' => auth()->id()]
        );

        HsmCert::updateOrCreate(
            ['user_id' => auth()->id(), 'label' => $csr->key_label],
            [
                'key_id'               => $hsmKey->id,
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
                'issuer_label'         => $issuerLabel,
            ]
        );

        $csr->update([
            'is_signed'       => true,
            'certificate_pem' => $certPem,
            'serial_number'   => $meta['serial_hex'],
            'issuer_label'    => $issuerLabel,
            'issued_at'       => $meta['valid_from'],
            'expires_at'      => $meta['valid_until'],
        ]);

        AuditLogger::log('csr.signed', "HSM CSR '{$csr->key_label}' signed by CA '{$issuerLabel}'.");

        $this->successMessage = "Certificate for '{$csr->key_label}' issued and stored in hsm_certs.";
    }

    public function downloadCertificate(int $csrId)
    {
        $this->error = '';

        $csr = HsmCsrFile::where('id', $csrId)->where('user_id', auth()->id())->first();
        if (! $csr || ! $csr->is_signed) {
            $this->error = 'No signed certificate for this CSR.';
            return;
        }

        $cert = HsmCert::where('user_id', auth()->id())
            ->where('label', $csr->key_label)
            ->latest()
            ->first();

        if (! $cert) {
            $this->error = 'Certificate record missing from hsm_certs.';
            return;
        }

        $content = $cert->chain_pem ?: $cert->certificate_pem;
        $name = Str::slug($csr->key_label) . '_' . $csr->id . '.crt';

        AuditLogger::log('certificate.downloaded', "Certificate for '{$csr->key_label}' downloaded (full chain).");

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $name, ['Content-Type' => 'application/x-pem-file']);
    }

    public function downloadByLabel(string $label)
    {
        $this->error = '';

        $cert = HsmCert::where('user_id', auth()->id())->where('label', $label)->latest()->first();
        if (! $cert) {
            $this->error = 'Certificate record not found.';
            return;
        }

        $content = $cert->chain_pem ?: $cert->certificate_pem;
        $name = Str::slug($label) . '.crt';

        AuditLogger::log('certificate.downloaded', "Certificate for '{$label}' downloaded (full chain).");

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $name, ['Content-Type' => 'application/x-pem-file']);
    }

    public function parseHsmObjects(): array
    {
        try {
            $output = $this->hsm()->listObjects();
        } catch (\Throwable) {
            return [];
        }

        if ($output === '') {
            return [];
        }

        $pattern = '/(?<type>Public Key|Private Key|Certificate) Object.*?\n(?<content>.*?)(?=(?:Public Key|Private Key|Certificate) Object|\z)/s';
        preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);

        $parsed = [];
        $labels = [];
        foreach ($matches as $match) {
            if (preg_match('/label:\s*(.*)/', $match['content'], $m)) {
                $labels[] = trim($m[1]);
            }
        }

        $dbKeys = HsmKey::whereIn('label', array_unique($labels))
            ->where('user_id', auth()->id())
            ->get()
            ->keyBy('label');

        foreach ($matches as $match) {
            $content = $match['content'];
            $label = preg_match('/label:\s*(.*)/', $content, $m) ? trim($m[1]) : '-';

            $dbKey = $dbKeys->get($label);
            $hsmId = preg_match('/ID:\s*([0-9a-fA-F]+)/', $content, $m) ? strtolower($m[1]) : null;

            $detail = '-';
            if (preg_match('/Usage:\s*(.*)/', $content, $m)) {
                $detail = 'Usage: ' . trim($m[1]);
            } elseif (preg_match('/subject:\s*(.*)/', $content, $m)) {
                $detail = 'Subject: ' . trim($m[1]);
            }

            $parsed[] = [
                'type'   => $match['type'],
                'label'  => $label,
                'id'     => $hsmId ?: ($dbKey ? $dbKey->id : '-'),
                'db_id'  => $dbKey ? $dbKey->id : null,
                'detail' => $detail,
            ];
        }

        return $parsed;
    }

    public function syncHsmKeys(): void
    {
        try {
            $output = $this->hsm()->listObjects();
        } catch (\Throwable) {
            return;
        }

        preg_match_all('/label:\s*(.*)/', $output, $matches);

        $labels = [];
        foreach (($matches[1] ?? []) as $label) {
            $labels[] = trim($label);
        }
        $labels = array_unique($labels);

        HsmKey::where('user_id', auth()->id())
            ->whereNotIn('label', $labels)
            ->get()
            ->each(function (HsmKey $key) {
                AuditLogger::log('key.synced.deleted', "Key '{$key->label}' removed from DB (no longer present in HSM).");
                $key->delete();
            });
    }

    public function render()
    {
        $this->syncHsmKeys();

        $hsmObjects = $this->parseHsmObjects();

        return view('livewire.hsm-dashboard', [
            'hsmObjects'  => $hsmObjects,
            'csrKeys'     => collect($hsmObjects)->filter(fn ($obj) => ($obj['type'] ?? '') === 'Public Key')->values(),
            'hsmCsrFiles' => HsmCsrFile::where('user_id', auth()->id())->latest()->get(),
            'hsmCerts'    => HsmCert::where('user_id', auth()->id())->latest()->get(),
            'hsmCaLabels' => $this->hsm()->listCaCertificates(),
        ])->layout('layouts.app');
    }

    private function csrStoragePath(string $keyLabel): string
    {
        $dir = storage_path('app/csr/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($keyLabel) . '_' . Str::random(8) . '.csr';
    }

    private function certStoragePath(int $csrId, string $keyLabel): string
    {
        $dir = storage_path('app/cert/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . $csrId . '_' . Str::slug($keyLabel) . '.crt';
    }

    private function serialStoragePath(string $issuerLabel): string
    {
        $dir = storage_path('app/serial/' . auth()->id());
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($issuerLabel) . '.srl';
    }
}