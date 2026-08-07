<?php

namespace App\Livewire;

use App\Models\Ca;
use App\Support\AuditLogger;
use App\Support\CertificateService;
use App\Support\HsmService;
use App\Support\IssuerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class IssuerCa extends Component
{
    public string $error = '';
    public string $successMessage = '';

    public bool $showForm = false;

    public string $keyLabel = '';
    public string $commonName = '';
    public string $organization = '';
    public string $country = '';
    public string $signerLabel = '';

    protected function rules(): array
    {
        return [
            'keyLabel'     => 'required|string|max:255|unique:cas,label',
            'commonName'   => 'required|string|max:255',
            'organization' => 'nullable|string|max:255',
            'country'      => 'nullable|string|size:2',
            'signerLabel'  => 'required|string',
        ];
    }

    private function hsm(): HsmService
    {
        return app(HsmService::class);
    }

    private function issuerService(): IssuerService
    {
        return app(IssuerService::class);
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
        $this->resetErrorBag();
    }

    public function createIssuer(): void
    {
        $validated = $this->validate();

        $this->error = '';
        $this->successMessage = '';

        $csrPath = $this->csrStoragePath($validated['keyLabel']);
        $certPath = $this->certStoragePath($validated['keyLabel']);
        $serialPath = $this->serialStoragePath($validated['signerLabel']);

        $keyCreated = false;

        try {
            // 2. HSM'de anahtar çiftini (Key Pair) oluştur
            $this->issuerService()->createKeyPair($validated['keyLabel'], 4096);
            $keyCreated = true;

            // 3. CSR dosyasını oluştur
            $this->issuerService()->createCsr(
                $validated['keyLabel'],
                $validated['commonName'],
                $validated['organization'] ?: null,
                $validated['country'] ?: null,
                $csrPath
            );

            // 4. Root CA ile CSR'ı imzala ve sertifikayı üret
            $this->issuerService()->signWithRoot(
                $csrPath,
                $validated['signerLabel'],
                $certPath,
                $serialPath,
                $days = 365 
            );

            // 5. Sertifikayı oku ve meta verilerini parse et
            $certPem = (string) file_get_contents($certPath);
            $meta = $this->certService()->parse($certPem);

            // 6. HSM'e certificate olarak yaz ve veritabanına (cas tablosuna) kaydet
            $ca = $this->issuerService()->saveIssuer(
                $validated['keyLabel'],
                $validated['commonName'],
                $validated['organization'] ?: null,
                $validated['country'] ? strtoupper($validated['country']) : null,
                $validated['signerLabel'],
                $certPem,
                $meta
            );
        } catch (\Throwable $e) {
            if ($keyCreated) {
                $this->hsm()->deleteKeyPair($validated['keyLabel']);
                $this->hsm()->deleteCertificate($validated['keyLabel']);
                Log::warning("Removed orphaned HSM key '{$validated['keyLabel']}' after failed issuer CA creation.");
            }

            $this->error = 'Issuer CA creation failed: ' . $e->getMessage();
            Log::error('Issuer CA creation: ' . $e->getMessage(),[
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'keyLabel' => $validated['keyLabel'] ?? null,
            ]);

           
            return;
        }

        AuditLogger::log('issuer_ca.created', "Issuer CA '{$ca->label}' created and signed by '{$validated['signerLabel']}'.");

        $this->successMessage = "Issuer CA '{$ca->label}' successfully created and signed.";
        $this->reset(['keyLabel', 'commonName', 'organization', 'country', 'signerLabel', 'showForm']);
    }

    public function downloadCertificate(int $id)
    {
        $this->error = '';

        $ca = Ca::find($id);
        if (! $ca) {
            $this->error = 'CA record not found.';
            return;
        }

        $name = Str::slug($ca->label) . '.crt';

        AuditLogger::log('issuer_ca.downloaded', "Certificate for issuer CA '{$ca->label}' downloaded.");

        return response()->streamDownload(function () use ($ca) {
            echo $ca->certificate;
        }, $name, ['Content-Type' => 'application/x-pem-file']);
    }

    public function render()
    {
        return view('livewire.issuer-ca', [
            'cas'           => Ca::orderByDesc('id')->get(),
            'signerOptions' => $this->signerOptions(),
        ])->layout('layouts.app');
    }

    private function signerOptions(): array
    {
        $hsmLabels = $this->hsm()->listCaCertificates();
        $dbLabels = Ca::where('is_active', true)->pluck('label')->all();

        $labels = array_values(array_unique(array_merge($dbLabels, $hsmLabels)));

        return array_values(array_filter(
            $labels,
            fn (string $label) => $this->hsm()->hasPrivateKey($label)
        ));
    }

    private function csrStoragePath(string $keyLabel): string
    {
        $dir = storage_path('app/issuer-csr');
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($keyLabel) . '_' . Str::random(8) . '.csr';
    }

    private function certStoragePath(string $keyLabel): string
    {
        $dir = storage_path('app/issuer-cert');
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($keyLabel) . '_' . Str::random(8) . '.crt';
    }

    private function serialStoragePath(string $signerLabel): string
    {
        $dir = storage_path('app/issuer-serial');
        if (! is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . Str::slug($signerLabel) . '.srl';
    }
}