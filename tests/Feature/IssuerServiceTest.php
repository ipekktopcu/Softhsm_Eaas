<?php

namespace Tests\Feature;

use App\Support\IssuerService;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class IssuerServiceTest extends TestCase
{
    private function issuer(): IssuerService
    {
        return app(IssuerService::class);
    }

    public function test_create_key_pair_throws_when_label_already_exists(): void
    {
        Process::fake([
            '*' => Process::result("Private Key Object; RSA\n  label:  web\n  ID:  aabb\n"),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Private key with label 'web' already exists");

        $this->issuer()->createKeyPair('web');
    }

    public function test_create_key_pair_generates_when_label_is_free(): void
    {
        Process::fake([
            '*--list-objects*' => Process::result(''),
            '*' => Process::result(),
        ]);

        $this->issuer()->createKeyPair('new-key', 4096);

        Process::assertRan(fn (PendingProcess $process) => str_contains($process->command, 'p11keygen')
            && str_contains($process->command, '-b 4096')
            && str_contains($process->command, 'new-key'));
    }

    public function test_sign_with_root_uses_valid_copy_extensions_option(): void
    {
        Process::fake([
            '*--list-objects*' => Process::result("Private Key Object; RSA\n  label:  Root CA\n  ID:  ab\n"),
            '*' => Process::result(),
        ]);

        $this->issuer()->signWithRoot('/tmp/csr.pem', 'Root CA', '/tmp/cert.pem', '/tmp/serial.srl', 365);

        Process::assertRan(fn (PendingProcess $process) => str_contains($process->command, 'openssl x509')
            && str_contains($process->command, '-copy_extensions copy')
            && ! str_contains($process->command, '-copy_extensions -out'));
    }

    public function test_sign_with_root_rejects_unknown_signer(): void
    {
        Process::fake([
            '*' => Process::result(''),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Signer private key with label 'Missing CA' does not exist");

        $this->issuer()->signWithRoot('/tmp/csr.pem', 'Missing CA', '/tmp/cert.pem', '/tmp/serial.srl');
    }
}
