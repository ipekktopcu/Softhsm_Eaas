<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UserManagement extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'user';

    public bool $showCreateForm = false;

    public string $search = '';

    public string $successMessage = '';

    public string $error = '';

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->resetErrorBag();
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'user';
    }

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        AuditLogger::log('user.created', "Admin created user '{$user->email}' with role '{$user->role}'.");

        $this->successMessage = "User '{$user->email}' created successfully.";
        $this->toggleCreateForm();
    }

    public function updateRole(int $userId, string $role): void
    {
        $user = User::findOrFail($userId);

        if (! in_array($role, User::ROLES, true)) {
            $this->error = 'Invalid role selected.';

            return;
        }

        if ($user->id === auth()->id() && $user->role === User::ROLE_ADMIN && $role !== User::ROLE_ADMIN) {
            $this->error = 'You cannot remove your own admin role.';

            return;
        }

        $oldRole = $user->role;
        $user->update(['role' => $role]);

        AuditLogger::log('user.role_changed', "Role for '{$user->email}' changed from '{$oldRole}' to '{$role}'.");

        $this->successMessage = "Role updated for '{$user->email}'.";
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->error = 'You cannot delete your own account.';

            return;
        }

        $adminCount = User::where('role', User::ROLE_ADMIN)->count();
        if ($user->role === User::ROLE_ADMIN && $adminCount <= 1) {
            $this->error = 'Cannot delete the last admin user.';

            return;
        }

        $email = $user->email;
        $user->delete();

        AuditLogger::log('user.deleted', "Admin deleted user '{$email}'.");

        $this->successMessage = "User '{$email}' deleted.";
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%'))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.user-management', [
            'users' => $users,
        ])->layout('layouts.app');
    }
}
