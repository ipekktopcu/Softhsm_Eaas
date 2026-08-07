<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">User Management</h1>
            </div>
            <button wire:click="toggleCreateForm" type="button"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition whitespace-nowrap">
                @if($showCreateForm)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Cancel
                @else
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New User
                @endif
            </button>
        </div>

        @if($error)
            <div class="flex items-start gap-3 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200">
                <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <span>{{ $error }}</span>
            </div>
        @endif

        @if($successMessage)
            <div class="flex items-start gap-3 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200">
                <svg class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <span>{{ $successMessage }}</span>
            </div>
        @endif

        @if($showCreateForm)
            <div class="bg-white rounded-xl border border-indigo-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-indigo-100 bg-indigo-50/60 flex items-center gap-3">
                    <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-indigo-600 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Create New User</h2>
                        <p class="text-xs text-gray-500">The new user can log in with these credentials.</p>
                    </div>
                </div>
                <form wire:submit.prevent="createUser" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. John Doe">
                            @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="email" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. john@example.com">
                            @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" wire:model="password" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Min. 8 characters">
                            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select wire:model="role" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="auditor">Auditor</option>
                            </select>
                            @error('role') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-indigo-100 text-indigo-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Users</h2>
                        <p class="text-xs text-gray-500">Manage accounts and their roles</p>
                    </div>
                </div>
                <div class="relative w-full max-w-xs">
                    <svg class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search users..."
                        class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="px-6 py-3 font-semibold">User</th>
                            <th class="px-6 py-3 font-semibold">Email</th>
                            <th class="px-6 py-3 font-semibold">Role</th>
                            <th class="px-6 py-3 font-semibold">Joined</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center h-9 w-9 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold uppercase overflow-hidden shrink-0">
                                            @if($user->profilePhotoUrl())
                                                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                            @else
                                                {{ $user->initials() }}
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 flex items-center gap-2">
                                                {{ $user->name }}
                                                @if($user->id === auth()->id())
                                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">YOU</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $badge = match($user->role) {
                                            'admin' => 'bg-indigo-100 text-indigo-700',
                                            'auditor' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-emerald-100 text-emerald-700',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $badge }} capitalize">{{ $user->role }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $user->created_at?->format('Y-m-d') ?: '-' }}</td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <select wire:change="updateRole({{ $user->id }}, $event.target.value)"
                                            class="rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                            @if($user->id === auth()->id()) disabled @endif>
                                            <option value="user" @selected($user->role === 'user')>User</option>
                                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                            <option value="auditor" @selected($user->role === 'auditor')>Auditor</option>
                                        </select>
                                        <button wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Delete user '{{ $user->email }}'? This cannot be undone."
                                            class="inline-flex items-center justify-center p-1.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Delete user" @if($user->id === auth()->id()) disabled @endif>
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 text-sm italic">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
