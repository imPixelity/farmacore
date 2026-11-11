<?php

namespace App\Providers;

use App\Modules\Core\Services\SettingsService;
use App\Modules\Core\ViewComposers\NotificationBellComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        $settings = $this->app->make(SettingsService::class);

        RateLimiter::for('login', function (Request $request) use ($settings): Limit {
            $username = Str::transliterate(Str::lower($request->string('username')->toString()));
            $maxAttempts = (int) $settings->get('login_max_attempts', 5);

            return Limit::perMinute(max(1, $maxAttempts))->by($username.'|'.$request->ip());
        });

        View::composer(['layouts.app', 'welcome'], NotificationBellComposer::class);
    }
}
