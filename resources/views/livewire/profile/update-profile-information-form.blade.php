<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->photo) {
            $this->validate(['photo' => ['image', 'max:2048']]);

            $path = $this->photo->store('profile-photos', 'public');

            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $path;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->photo = null;

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Remove the user's current profile photo.
     */
    public function removeProfilePhoto(): void
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div class="flex items-center gap-6">
            <div class="shrink-0">
                @if($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="Photo preview" class="h-20 w-20 rounded-full object-cover ring-2 ring-indigo-500">
                @elseif(auth()->user()->profilePhotoUrl())
                    <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="h-20 w-20 rounded-full object-cover ring-2 ring-gray-200">
                @else
                    <div class="flex items-center justify-center h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold uppercase">
                        {{ auth()->user()->initials() }}
                    </div>
                @endif
            </div>

            <div class="flex-1">
                <x-input-label for="photo" :value="__('Profile Photo')" />
                <input id="photo" type="file" wire:model="photo"
                    accept="/*"
                    class="mt-1 block w-full text-sm text-gray-500
                        file:mr-3 file:py-2 file:px-4 file:rounded-lg
                        file:border-0 file:text-sm file:font-medium
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100 transition">
                <x-input-error class="mt-2" :messages="$errors->get('photo')" />

                <div class="mt-2 flex items-center gap-4">
                    <span wire:loading wire:target="photo" class="text-xs text-gray-500">Uploading…</span>
                    @if(! $photo && auth()->user()->profilePhotoUrl())
                        <button type="button" wire:click="removeProfilePhoto"
                            class="text-xs font-medium text-red-600 hover:text-red-800 underline underline-offset-2 transition">
                            {{ __('Remove photo') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
