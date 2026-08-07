<?php

namespace Tests\Feature;

use App\Support\HsmService;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class HsmServiceTest extends TestCase
{
    private function hsm(): HsmService
    {
        return app(HsmService::class);
    }

    public function test_has_private_key_returns_true_when_label_exists(): void
    {
        Process::fake([
            '*' => Process::result("Private Key Object; RSA\n  label:      web\n  ID:         aabb\n"),
        ]);

        $this->assertTrue($this->hsm()->hasPrivateKey('web'));
    }

    public function test_has_private_key_returns_false_when_label_missing(): void
    {
        Process::fake([
            '*' => Process::result("Private Key Object; RSA\n  label:      other\n  ID:         ccdd\n"),
        ]);

        $this->assertFalse($this->hsm()->hasPrivateKey('web'));
    }

    public function test_delete_key_pair_removes_private_and_public_objects(): void
    {
        Process::fake();

        $this->hsm()->deleteKeyPair('web');

        Process::assertRan(fn (PendingProcess $process) => str_contains($process->command, '--delete-object')
            && str_contains($process->command, 'privkey'));
        Process::assertRan(fn (PendingProcess $process) => str_contains($process->command, '--delete-object')
            && str_contains($process->command, 'pubkey'));
    }

    public function test_write_certificate_uses_the_id_of_the_matching_private_key(): void
    {
        Process::fake([
            '*--list-objects*' => Process::result(
                "Private Key Object; RSA\n  label:  other\n  ID:  ddeeff\n"
                . "Private Key Object; RSA\n  label:  web\n  ID:  aabbcc\n"
            ),
            '*' => Process::result(),
        ]);

        $this->hsm()->writeCertificate(
            'web',
            "-----BEGIN CERTIFICATE-----\nAA==\n-----END CERTIFICATE-----\n"
        );

        Process::assertRan(fn (PendingProcess $process) => str_contains($process->command, '--write-object')
            && str_contains($process->command, 'aabbcc'));
    }
}
