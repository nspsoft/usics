<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-validate', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        // Fix for Cloudflare Tunnel / Reverse Proxy (Mixed Content Issue)
        // Check X-Forwarded-Proto OR if the Host is the secure domain
        if (\Illuminate\Support\Facades\Request::server('HTTP_X_FORWARDED_PROTO') === 'https' || 
            \Illuminate\Support\Facades\Request::server('HTTP_HOST') === 'erp.nsp.my.id' ||
            \Illuminate\Support\Facades\Request::server('HTTP_HOST') === 'jicos.jidoka.co.id') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Observers
        \App\Models\SalesOrder::observe(\App\Observers\OrderStatusObserver::class);

        Event::listen(Login::class, function ($event) {
            activity('auth')
                ->causedBy($event->user)
                ->tap(function($activity) {
                    $activity->event = 'login';
                })
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('User logged in');
        });

        Event::listen(Logout::class, function ($event) {
            if ($event->user) {
                activity('auth')
                    ->causedBy($event->user)
                    ->tap(function($activity) {
                        $activity->event = 'logout';
                    })
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ])
                    ->log('User logged out');
            }
        });

        Event::listen(Failed::class, function ($event) {
            activity('auth')
                ->tap(function($activity) {
                    $activity->event = 'failed';
                })
                ->withProperties([
                    'email' => $event->credentials['email'] ?? 'unknown',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Failed login attempt');
        });
    }
}
