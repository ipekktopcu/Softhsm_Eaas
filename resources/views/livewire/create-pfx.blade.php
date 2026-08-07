<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Certificate &amp; PFX Management</h1>
            </div>
            <button wire:click="toggleForm" type="button"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition whitespace-nowrap">
                {{ $showForm ? 'Cancel' : 'New Key Pair' }}
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-5">
            <h1 class="text-sm font-semibold text-gray-900 mb-4">Workflow</h1>
            <div class="flex items-center justify-between max-w-3xl mx-auto">
                @php
                    $steps = [
                        ['n' => 1, 'label' => 'Key Pair', 'desc' => 'Generate (OpenSSL)'],
                        ['n' => 2, 'label' => 'CSR', 'desc' => 'Create request'],
                        ['n' => 3, 'label' => 'Sign', 'desc' => 'Issuer CA issues cert'],
                        ['n' => 4, 'label' => 'PFX', 'desc' => 'Export bundle'],
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
            <div class="flex items-start gap-3 p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200">
                <span>{{ $error }}</span>
            </div>
        @endif
        @if($successMessage)
            <div class="flex items-start gap-3 p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200">
                <span>{{ $successMessage }}</span>
            </div>
        @endif

        @if($showForm)
            <div class="bg-white rounded-xl border border-indigo-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-indigo-100 bg-indigo-50/60">
                    <h2 class="text-sm font-semibold text-gray-900">Generate New Key Pair</h2>
                    <p class="text-xs text-gray-500">Creates a private key stored securely (encrypted) in the database. No SoftHSM involved.</p>
                </div>
                <form wire:submit.prevent="generateKeyPair" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Key Label</label>
                        <input type="text" wire:model="keyLabel" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. my-server-key">
                        @error('keyLabel') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">Save Key</button>
                    </div>
                </form>
            </div>
        @endif

        @if($showCsrForm)
            <div class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-emerald-100 bg-emerald-50/60">
                    <h2 class="text-sm font-semibold text-gray-900">Create Certificate Signing Request</h2>
                </div>
                <form wire:submit.prevent="generateCsr" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Key Label</label>
                            <select wire:model="keyLabel" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">-- Select Key --</option>
                                @foreach($leafKeys as $k)
                                    <option value="{{ $k->label }}">{{ $k->label }}</option>
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

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Database Keys</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Key Label</th>
                            <th class="px-6 py-3 font-semibold">Storage</th>
                            <th class="px-6 py-3 font-semibold">Created At</th>
                            <th class="px-6 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($leafKeys as $key)
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-6 py-4 font-bold text-indigo-600 font-mono">{{ $key->label }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Database</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $key->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    <button wire:click="prepareCsrForm('{{ $key->label }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-indigo-200 text-indigo-600 text-xs font-medium rounded-lg hover:bg-indigo-50 transition">
                                        Use for CSR
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm italic">No keys yet. Generate your first key pair to get started.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">CSR Files</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Key Label</th>
                            <th class="px-6 py-3 font-semibold">Common Name</th>
                            <th class="px-6 py-3 font-semibold">Issuer</th>
                            <th class="px-6 py-3 font-semibold">Created At</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold">Valid Until</th>
                            <th class="px-6 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($csrFiles as $csr)
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-6 py-4 font-bold text-gray-900 font-mono">{{ $csr->key_label }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $csr->common_name }}</td>
                                <td class="px-6 py-4 text-gray-500">
                                    @if($csr->is_signed)
                                        {{ $pfxCerts[$csr->key_label]->issuer_label ?? '-' }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $csr->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($csr->is_signed)
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Signed</span>
                                        @if($csr->serial_number)
                                            <span class="block text-[10px] text-gray-400 mt-1" title="Serial Number">SN: {{ $csr->serial_number }}</span>
                                        @endif
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Unsigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    @if($csr->is_signed)
                                        {{ $csr->expires_at?->format('Y-m-d') ?: '-' }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if(! $csr->is_signed)
                                        <button wire:click="prepareSignForm({{ $csr->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition">
                                            Sign
                                        </button>
                                    @else
                                        <button wire:click="downloadCertificate('{{ $csr->key_label }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-green-200 text-green-700 text-xs font-medium rounded-lg hover:bg-green-50 transition">
                                            Download Cert
                                        </button>
                                        <button wire:click="openPfxModal('{{ $csr->key_label }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                                            Create &amp; Download PFX
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400 text-sm italic">No CSRs yet. Use "Use for CSR" on a key to create one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Issued Certificates</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Label</th>
                            <th class="px-6 py-3 font-semibold">Subject</th>
                            <th class="px-6 py-3 font-semibold">Issuer</th>
                            <th class="px-6 py-3 font-semibold">Serial</th>
                            <th class="px-6 py-3 font-semibold">Valid Until</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($pfxCerts as $cert)
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-6 py-4 font-bold text-indigo-600 font-mono">{{ $cert->label }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $cert->subject }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $cert->issuer_label ?: '-' }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $cert->serial_number }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $cert->valid_until?->format('Y-m-d') ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">{{ ucfirst($cert->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400 text-sm italic">No certificates issued yet. Sign a CSR to create one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    

    @if($showSignForm)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Choose issuer CA (database)</h3>
                <select wire:model="selectedCaId" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 mb-1">
                    <option value="">-- Select CA --</option>
                    @foreach($availableCas as $ca)
                        <option value="{{ $ca['id'] }}">{{ $ca['label'] }} ({{ $ca['level'] }})</option>
                    @endforeach
                </select>
                @error('selectedCaId') <span class="text-red-500 text-xs block mb-3">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="cancelSignForm" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="button" wire:click="confirmSignCsr"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                        wire:loading.attr="disabled" wire:target="confirmSignCsr">
                        <span wire:loading.remove wire:target="confirmSignCsr">Sign</span>
                        <span wire:loading wire:target="confirmSignCsr">Signing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showPfxModal)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-200">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 leading-tight">Create PFX Certificate</h3>
                        <p class="text-xs text-gray-500">Packages certificate, full chain &amp; private key</p>
                    </div>
                    <button wire:click="closePfxModal" type="button" class="text-gray-400 hover:text-gray-600 transition">✕</button>
                </div>

                <div class="mb-4 flex items-center gap-2 px-3 py-2.5 rounded-lg bg-indigo-50 text-sm text-indigo-700">
                    <span class="font-mono font-semibold">{{ $selectedLabel }}</span>
                </div>

                <form wire:submit.prevent="generatePfx">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">PFX Password</label>
                        <input type="password" wire:model="pfxPassword" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Enter password for PFX" required>
                        @error('pfxPassword') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        <p class="mt-1 text-[11px] text-gray-400">You'll need this password to install the PFX.</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button wire:click="closePfxModal" type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">Generate &amp; Download</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>