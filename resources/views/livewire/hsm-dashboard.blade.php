<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between mb-4">
            <h2 class="text-lg font-bold whitespace-nowrap">HSM Management</h2>
            <div class="flex items-center justify-end gap-2">
                <button wire:click="toggleForm" type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition whitespace-nowrap">
                    {{ $showForm ? 'Cancel' : 'Generate New Key Pair' }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-5 mb-6">
            <h1 class="text-sm font-semibold text-gray-900 mb-4">Workflow</h1>
            <div class="flex items-center justify-between max-w-3xl mx-auto">
                @php
                    $steps = [
                        ['n' => 1, 'label' => 'Key Pair', 'desc' => 'Generate key in the SoftHSM'],
                        ['n' => 2, 'label' => 'CSR', 'desc' => 'Create request'],
                        ['n' => 3, 'label' => 'Sign', 'desc' => 'HSM CA issues cert'],
                    ];
                @endphp
                @foreach($steps as $step)
                    <div class="flex items-center flex-1">
                        <div class="flex flex-col items-center text-center gap-1.5">
                            <div class="flex items-center justify-center h-9 w-9 rounded-full bg-indigo-600 text-white text-sm font-bold shadow-sm">{{ $step['n'] }}</div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ $step['label'] }}</div>
                                <div class="text-[11px] text-gray-500 hidden sm:block">{{ $step['desc'] }}</div>
                            </div>
                        </div>
                        @if(! $loop->last)
                            <div class="flex-1 h-px bg-gray-200 mx-3 mb-5"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if($error)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ $error }}</div>
        @endif
        @if($successMessage)
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded mb-4">{{ $successMessage }}</div>
        @endif

        @if($showForm)
            <div class="p-4 border border-gray-200 bg-gray-50 rounded-md mb-6">
                <form wire:submit.prevent="generateKeyPair" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Key Label</label>
                            <input type="text" wire:model="keyLabel" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Enter key label">
                            @error('keyLabel') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-black rounded-md hover:bg-green-700 text-sm font-medium"
                            wire:loading.attr="disabled" wire:target="generateKeyPair">
                            <span wire:loading.remove wire:target="generateKeyPair">Create Keys</span>
                            <span wire:loading wire:target="generateKeyPair">Generating...</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if($showCsrForm)
            <div class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-emerald-100 bg-emerald-50/60">
                    <h2 class="text-sm font-semibold text-gray-900">Create Certificate Signing Request</h2>
                </div>
                <form wire:submit.prevent="generateCsr" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Key Label</label>
                            <select wire:model="keyLabel" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">-- Select Key --</option>
                                @foreach($csrKeys as $k)
                                    <option value="{{ $k['label'] }}">{{ $k['label'] }}</option>
                                @endforeach
                            </select>
                            @error('keyLabel') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Common Name (CN) <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="commonName" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. example.com">
                            @error('commonName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization (O)</label>
                            <input type="text" wire:model="organization" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. Company">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country (C)</label>
                            <input type="text" wire:model="country" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" maxlength="2" placeholder="TR">
                            @error('country') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="toggleFormCsr" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition">Generate CSR</button>
                    </div>
                </form>
            </div>
        @endif

        

        @php
            $keysList = collect($hsmObjects)->filter(fn($obj) => ($obj['type'] ?? '') === 'Public Key');
            $certList = collect($hsmObjects)->filter(fn($obj) => ($obj['type'] ?? '') === 'Certificate');
        @endphp

        <div class="mb-8">
            <h4 class="text-md font-bold text-gray-700 mb-3 border-b pb-2">Cryptographic Keys</h4>
            <div class="w-full overflow-x-auto bg-white rounded-lg border border-gray-200 shadow-sm">
                <table class="w-full text-sm text-left table-auto divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Object Type</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Label</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Details</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($keysList as $obj)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    @if($obj['type'] === 'Public Key')
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Public Key</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">Private Key</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $obj['label'] }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $obj['detail'] }}</td>
                                <td class="px-6 py-4">
                                    <button wire:click="prepareCsrForm('{{ $obj['label'] }}')" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-indigo-200 text-indigo-600 text-xs font-medium rounded-lg hover:bg-indigo-50 transition">
                                        Generate CSR
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">No keys found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">CSR Files</h2>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-3 text-center">Key Label</th>
                            <th class="px-6 py-3 text-center">Common Name</th>
                            <th class="px-6 py-3 text-center">Created At</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Issuer</th>
                            <th class="px-6 py-3 text-center">Valid Until</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($hsmCsrFiles as $csr)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">{{ $csr->key_label }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $csr->common_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $csr->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($csr->is_signed)
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-green-100 text-green-800">Signed</span>
                                    @else
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-yellow-100 text-yellow-800">Unsigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $csr->issuer_label ?: '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $csr->expires_at?->format('Y-m-d') ?: '-' }}</td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if($csr->is_signed)
                                        <button wire:click="downloadCertificate({{ $csr->id }})" type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-green-200 text-green-700 text-xs font-medium rounded-lg hover:bg-green-50 transition">
                                            Download Certificate
                                        </button>
                                    @else
                                        <button wire:click="prepareSignForm({{ $csr->id }})" type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-indigo-200 text-indigo-600 text-xs font-medium rounded-lg hover:bg-indigo-50 transition">
                                            Sign CSR
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">No CSR files found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Issued Certificates</h2>
        </div>
        <div class="w-full overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-3">Label</th>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Issuer</th>
                            <th class="px-6 py-3">Valid Until</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($hsmCerts as $cert)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">{{ $cert->label }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ is_array($cert->subject) ? implode(', ', $cert->subject) : $cert->subject }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $cert->issuer_label ?: '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $cert->valid_until?->format('Y-m-d') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ ucfirst($cert->status) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="downloadByLabel('{{ $cert->label }}')" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-green-200 text-green-700 text-xs font-medium rounded-lg hover:bg-green-50 transition">
                                        Download Full Chain
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">No certificates issued yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
         @if($showSignForm)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Choose issuer CA to sign the CSR</h3>
                <select wire:model="selectedIssuer" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 mb-1">
                    <option value="">-- Select CA --</option>
                    @foreach($hsmCAs as $ca)
                        <option value="{{ $ca }}">{{ $ca }}</option>
                    @endforeach
                </select>
                @error('selectedIssuer') <span class="text-red-500 text-xs block mb-3">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="cancelSignForm" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="button" wire:click="confirmSignCsr"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                        wire:loading.attr="disabled" wire:target="confirmSignCsr">
                        <span wire:loading.remove wire:target="confirmSignCsr">Approve and Sign</span>
                        <span wire:loading wire:target="confirmSignCsr">Signing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    </div>


   
