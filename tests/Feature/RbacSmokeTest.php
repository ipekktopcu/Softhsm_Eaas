<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RbacSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $role = 'user'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_admin_can_access_users_and_logs(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('User Management');
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/create-pfx')->assertOk();
    }

    public function test_user_cannot_access_admin_pages(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/logs')->assertForbidden();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/create-pfx')->assertOk();
    }

    public function test_auditor_can_only_access_logs(): void
    {
        $auditor = $this->user('auditor');

        $this->actingAs($auditor)->get('/admin/logs')->assertOk();
        $this->actingAs($auditor)->get('/admin/users')->assertForbidden();
        $this->actingAs($auditor)->get('/dashboard')->assertForbidden();
        $this->actingAs($auditor)->get('/create-pfx')->assertForbidden();
    }

    public function test_auditor_login_redirects_to_logs(): void
    {
        $auditor = $this->user('auditor');

        Volt::test('pages.auth.login')
            ->set('form.email', $auditor->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.logs', absolute: false));

        $this->assertAuthenticatedAs($auditor);
    }

    public function test_audit_logs_are_immutable(): void
    {
        $log = AuditLog::create(['action' => 'test.action', 'description' => 'x']);

        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\UserManagement::class)
            ->set('name', 'New Person')
            ->set('email', 'new@example.com')
            ->set('password', 'password123')
            ->set('role', 'auditor')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'auditor']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\UserManagement::class)
            ->call('deleteUser', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_users_see_only_their_own_keys(): void
    {
        $userA = $this->user();
        $userB = $this->user();

        \App\Models\LeafKey::create([
            'user_id' => $userA->id,
            'label' => 'key-a',
            'common_name' => 'example.com',
            'organization' => 'Test Org',
            'country' => 'TR',
            'private_key' => 'a',
            'serial_number' => 'aaa',
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($userB);

        Livewire::test(\App\Livewire\CreatePfx::class)
            ->assertViewHas('leafKeys', fn ($keys) => $keys->isEmpty());
    }
}
