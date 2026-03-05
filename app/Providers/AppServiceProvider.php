<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
    
    }
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        if (str_contains(config('app.url'), 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            
            request()->server->set('HTTPS', 'on'); 
        }

        Event::listen(function (Login $event) {
            activity('authentication')
                ->performedOn($event->user)
                ->log('User logged in');
        });

        Event::listen(function (Logout $event) {
            if ($event->user) {
                activity('authentication')
                    ->performedOn($event->user)
                    ->log('User logged out');
            }
        });

        Event::listen(function (Failed $event) {
            $username = $event->credentials['username'] ?? $event->credentials['email'] ?? 'unknown';
            activity('authentication')
                ->withProperties(['attempted_username' => $username])
                ->log('Failed login attempt');
        });
    }
}
