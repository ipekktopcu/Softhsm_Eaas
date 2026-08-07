<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between mb-4">
            <h2 class="text-lg font-bold whitespace-nowrap">Issuer CA Management</h2>
            <div class="flex items-center justify-end gap-2">
                <button wire:click="toggleForm" type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition whitespace-nowrap">
                    {{ $showForm ? 'Cancel' : 'Create New Issuer CA' }}
                </button>
            </div>
        </div>

        @if($error)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ $error }}</div>
        @endif
        @if($successMessage)
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded mb-4">{{ $successMessage }}</div>
        @endif

        @if($showForm)
            <div class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-emerald-100 bg-emerald-50/60">
                    <h2 class="text-sm font-semibold text-gray-900">Generate, Request &amp; Sign a New Issuer CA</h2>
                    <p class="text-xs text-gray-500 mt-1">This will create a key pair in the HSM, build a CA CSR, and sign it with the selected issuer.</p>
                </div>
                <form wire:submit.prevent="createIssuer" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Key Label <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="keyLabel" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. Intermediate CA 2">
                            @error('keyLabel') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Common Name (CN) <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="commonName" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. Ipek Intermediate CA 2">
                            @error('commonName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization (O)</label>
                            <input type="text" wire:model="organization" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. IpekOrg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country (C)</label>
                            <input type="text" wire:model="country" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" maxlength="2" placeholder="TR">
                            @error('country') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sign With (Issuer) <span class="text-red-500">*</span></label>
                            <select wire:model="signerLabel" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">-- Select Signing CA --</option>
                                @foreach($signerOptions as $signer)
                                    <option value="{{ $signer }}">{{ $signer }}</option>
                                @endforeach
                            </select>
                            @error('signerLabel') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            @if(empty($signerOptions))
                                <p class="text-xs text-amber-600 mt-1">No signing CAs found in HSM (expected at least "Root CA").</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="toggleForm" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                            wire:loading.attr="disabled" wire:target="createIssuer">
                            <span wire:loading.remove wire:target="createIssuer">Create &amp; Sign Issuer CA</span>
                            <span wire:loading wire:target="createIssuer">Creating...</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Issuer CAs</h2>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-3">Label</th>
                            <th class="px-6 py-3">Level</th>
                            <th class="px-6 py-3">Common Name</th>
                            <th class="px-6 py-3">Issuer</th>
                            <th class="px-6 py-3">Valid Until</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($cas as $ca)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">{{ $ca->label }}</td>
                                <td class="px-6 py-4">
                                    @if($ca->level === 'root')
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Root</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-sky-100 text-sky-800">Intermediate</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $ca->common_name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $ca->issuer_label ?: '-' }}</td>
                                <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $ca->valid_until?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @if(! $ca->is_active)
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Inactive</span>
                                    @elseif($ca->is_expired)
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Valid</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="downloadCertificate({{ $ca->id }})" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-green-200 text-green-700 text-xs font-medium rounded-lg hover:bg-green-50 transition">
                                        Download Certificate
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">No issuer CAs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>