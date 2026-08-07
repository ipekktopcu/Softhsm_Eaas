<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\HsmDashboard;
use App\Livewire\CreatePfx;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Volt;


Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isAuditor() ? 'admin.logs' : 'dashboard');
    }

    return redirect()->route('login');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', HsmDashboard::class)->middleware('role:user,admin')->name('dashboard');
    Route::get('/create-pfx', CreatePfx::class)->middleware('role:user,admin')->name('create-pfx');
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/admin/users', App\Livewire\UserManagement::class)->middleware('role:admin')->name('admin.users');
    Route::get('/admin/logs', App\Livewire\AuditLogs::class)->middleware('role:auditor,admin')->name('admin.logs');
   Route::get('livewire/issuer-ca', App\Livewire\IssuerCa::class)->middleware('role:admin')->name('admin.issuer');
});
require __DIR__.'/auth.php';
