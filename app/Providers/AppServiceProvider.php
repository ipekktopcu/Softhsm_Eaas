<?php

namespace App\Providers;

use App\Support\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         if (request()->header('X-Forwarded-Proto') === 'https' || str_contains(request()->getHost(), 'trycloudflare.com')) {
            URL::forceScheme('https');
            // Tünel URL'sini dinamik olarak kök URL yapıyoruz ki resimler ve asset'ler şaşmasın
            URL::forceRootUrl('https://' . request()->getHost());
        }

        $this->registerAuditListeners();
    }

    private function registerAuditListeners(): void
    {
        $this->app['events']->listen(function (Login $event) {
            AuditLogger::log('auth.login', "User '{$event->user->email}' logged in.", $event->user);
        });

        $this->app['events']->listen(function (Logout $event) {
            AuditLogger::log('auth.logout', "User '{$event->user->email}' logged out.", $event->user);
        });
    }
}
