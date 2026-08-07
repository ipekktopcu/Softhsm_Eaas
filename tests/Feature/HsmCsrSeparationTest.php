<?php

namespace Tests\Feature;

use App\Livewire\HsmDashboard;
use App\Models\CsrFile;
use App\Models\HsmCsrFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HsmCsrSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_csrs_allowed_for_same_hsm_key(): void
    {
        $user = User::factory()->create();

        HsmCsrFile::create([
            'user_id' => $user->id,
            'key_label' => 'server-key',
            'common_name' => 'first.example.com',
            'organization' => 'Org',
            'country' => 'TR',
            'file_path' => storage_path('app/csr/1/server-key_first.csr'),
        ]);
        HsmCsrFile::create([
            'user_id' => $user->id,
            'key_label' => 'server-key',
            'common_name' => 'second.example.com',
            'organization' => 'Org',
            'country' => 'TR',
            'file_path' => storage_path('app/csr/1/server-key_second.csr'),
        ]);

        $this->assertSame(2, HsmCsrFile::where('user_id', $user->id)->where('key_label', 'server-key')->count());
    }

    public function test_dashboard_lists_only_hsm_csrs_not_leaf_csr_files(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        HsmCsrFile::create([
            'user_id' => $user->id,
            'key_label' => 'hsm-key',
            'common_name' => 'hsm.example.com',
            'organization' => 'Org',
            'country' => 'TR',
            'file_path' => storage_path('app/csr/1/hsm-key.csr'),
        ]);

        CsrFile::create([
            'user_id' => $user->id,
            'key_label' => 'leaf-key',
            'common_name' => 'leaf.example.com',
            'organization' => 'Org',
            'country' => 'TR',
            'file_path' => storage_path('app/leaf-key.csr'),
        ]);

        Livewire::test(HsmDashboard::class)
            ->assertViewHas('hsmCsrFiles', fn ($csrs) => $csrs->contains(fn ($c) => $c->key_label === 'hsm-key'))
            ->assertViewHas('hsmCsrFiles', fn ($csrs) => $csrs->doesntContain(fn ($c) => $c->key_label === 'leaf-key'));
    }
}
