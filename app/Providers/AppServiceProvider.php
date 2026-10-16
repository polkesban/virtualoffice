<?php

namespace App\Providers;

use App\Models\User;
use App\Support\GuideCatalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GuideCatalog::class, fn () => new GuideCatalog());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::refreshRoleConfig();

        $guideViews = [
            'layouts.app',
            'admin.layouts.admin',
            'mahasiswa.layouts.portal',
            'admin.login',
            'mahasiswa.login',
            'mahasiswa.register',
        ];

        View::composer($guideViews, function ($view) {
            $catalog = app(GuideCatalog::class);

            $view->with('contextualGuide', $catalog->current(request()->route()?->getName()));
        });

        // Define rate limiters
        RateLimiter::for('public-auth', function () {
            return Limit::perMinute(60);
        });

        RateLimiter::for('public-form', function () {
            return Limit::perMinute(10);
        });
    }
}
