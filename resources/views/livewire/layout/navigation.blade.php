<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-slate-900 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-6 sm:gap-10">
                <!-- Brand -->
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 shrink-0">
                        <img class="rounded-md ring-1 ring-black ring-opacity-5" height='400' width='150' src='{{ asset('images/logo.png') }}' alt='Application Logo' />
                    
                </a>

                <!-- Primary Navigation Links -->
                <div class="hidden sm:flex items-center gap-1">
                    @unless(auth()->user()->isAuditor())
                    <a href="{{ route('dashboard') }}" wire:navigate
                        class="{{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} px-3 py-2 rounded-md text-sm font-medium transition">
                        {{ __('HSM Keys') }}
                    </a>
                    <a href="{{ route('create-pfx') }}" wire:navigate
                        class="{{ request()->routeIs('create-pfx') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} px-3 py-2 rounded-md text-sm font-medium transition">
                        {{ __('PFX & Certificates') }}
                    </a>
                    @endunless

                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.issuer') }}" wire:navigate
                        class="{{ request()->routeIs('admin.issuer') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} px-3 py-2 rounded-md text-sm font-medium transition">
                        {{ __('Issuer CA') }}
                    </a>
                    @endif

                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.users') }}" wire:navigate
                        class="{{ request()->routeIs('admin.users') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} px-3 py-2 rounded-md text-sm font-medium transition">
                        {{ __('Users') }}
                    </a>
                    @endif

                    @if(auth()->user()->isAuditor() || auth()->user()->isAdmin())
                    <a href="{{ route('admin.logs') }}" wire:navigate
                        class="{{ request()->routeIs('admin.logs') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} px-3 py-2 rounded-md text-sm font-medium transition">
                        {{ __('Audit Logs') }}
                    </a>
                    @endif
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                            <div class="flex items-center justify-center h-7 w-7 rounded-full bg-indigo-500 text-white text-xs font-bold uppercase overflow-hidden">
                                @if(auth()->user()->profilePhotoUrl())
                                    <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ auth()->user()->initials() }}
                                @endif
                            </div>
                            <div class="text-left leading-tight">
                                <div class="text-white text-sm font-medium" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            </div>
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3">
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 text-sm font-bold uppercase overflow-hidden shrink-0">
                                @if(auth()->user()->profilePhotoUrl())
                                    <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ auth()->user()->initials() }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-300 hover:text-white hover:bg-slate-800 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 px-2">
            @unless(auth()->user()->isAuditor())
            <a href="{{ route('dashboard') }}" wire:navigate
                class="{{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} block px-3 py-2 rounded-md text-base font-medium transition">
                {{ __('HSM Keys') }}
            </a>
            <a href="{{ route('create-pfx') }}" wire:navigate
                class="{{ request()->routeIs('create-pfx') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} block px-3 py-2 rounded-md text-base font-medium transition">
                {{ __('PFX & Certificates') }}
            </a>
            @endunless

            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.issuer') }}" wire:navigate
                class="{{ request()->routeIs('admin.issuer') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} px-3 py-2 rounded-md text-sm font-medium transition">
                {{ __('Issuer CA') }}
            </a>
            @endif

            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.users') }}" wire:navigate
                class="{{ request()->routeIs('admin.users') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} block px-3 py-2 rounded-md text-base font-medium transition">
                {{ __('Users') }}
            </a>
            @endif

            @if(auth()->user()->isAuditor())
            <a href="{{ route('admin.logs') }}" wire:navigate
                class="{{ request()->routeIs('admin.logs') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }} block px-3 py-2 rounded-md text-base font-medium transition">
                {{ __('Audit Logs') }}
            </a>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-3 border-t border-slate-800">
            <div class="px-4 flex items-center gap-3">
                <div class="flex items-center justify-center h-10 w-10 rounded-full bg-indigo-500 text-white text-sm font-bold uppercase overflow-hidden">
                    @if(auth()->user()->profilePhotoUrl())
                        <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    @else
                        {{ auth()->user()->initials() }}
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="font-medium text-base text-white truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="font-medium text-sm text-slate-400 truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1 px-2">
                <a href="{{ route('profile') }}" wire:navigate class="block px-3 py-2 rounded-md text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                    {{ __('Profile') }}
                </a>

                <button wire:click="logout" class="w-full text-start">
                    <span class="block px-3 py-2 rounded-md text-base font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">{{ __('Log Out') }}</span>
                </button>
            </div>
        </div>
    </div>
</nav>
