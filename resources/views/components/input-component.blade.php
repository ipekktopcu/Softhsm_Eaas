<form wire:submit="generateKey">
    @csrf
    
    <!-- Key Label / ID -->
    <div>
        <x-input-label for="keyLabel" value="Key Label" />
        <x-text-input wire:model="keyLabel" id="keyLabel" type="text" class="block mt-1 w-full" required />
    </div>

    <!-- Common Name -->
    <div class="mt-4">
        <x-input-label for="commonName" value="Common Name (CN)" />
        <x-text-input wire:model="commonName" id="commonName" type="text" class="block mt-1 w-full" required />
    </div>

    <!-- Organization -->
    <div class="mt-4">
        <x-input-label for="organization" value="Organization (O)" />
        <x-text-input wire:model="organization" id="organization" type="text" class="block mt-1 w-full" required />
    </div>

    <!-- Country -->
    <div class="mt-4">
        <x-input-label for="country" value="Country (C)" />
        <x-text-input wire:model="country" id="country" type="text" class="block mt-1 w-full" maxlength="2" required />
    </div>

    <x-primary-button class="mt-4">
        Generate
    </x-primary-button>
</form>