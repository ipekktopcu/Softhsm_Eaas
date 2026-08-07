<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Audit Logs</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Immutable record of user activity. Logs can only be viewed — never edited or deleted.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center h-9 w-9 rounded-lg bg-amber-100 text-amber-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3 4.5V6.75m8.25 4.5A8.25 8.25 0 1 1 4.5 11.25a8.25 8.25 0 0 1 16.5 0Z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Activity Log</h2>
                        <p class="text-xs text-gray-500">Read-only — latest 500 entries</p>
                    </div>
                </div>
                <div class="relative w-full max-w-xs">
                    <svg class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search logs..."
                        class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="px-6 py-3 font-semibold">User</th>
                            <th class="px-6 py-3 font-semibold">Action</th>
                            <th class="px-6 py-3 font-semibold">Details</th>
                            <th class="px-6 py-3 font-semibold">IP Address</th>
                            <th class="px-6 py-3 font-semibold">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50/70 transition align-top">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold uppercase overflow-hidden shrink-0">
                                                @if($log->user->profilePhotoUrl())
                                                    <img src="{{ $log->user->profilePhotoUrl() }}" alt="{{ $log->user->name }}" class="h-full w-full object-cover">
                                                @else
                                                    {{ $log->user->initials() }}
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $log->user->name }}</div>
                                                <div class="text-xs text-gray-400">{{ $log->user->email }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">System</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 font-mono">{{ $log->action }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $log->description }}</td>
                                <td class="px-6 py-4 text-gray-400 font-mono text-xs">{{ $log->ip_address ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 text-sm italic">No log entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
