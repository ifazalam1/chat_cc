<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\SiteSettings;

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
        // Site settings singleton - shared from database
        app()->singleton('siteSettings', function () {
            return SiteSettings::first();
        });

        // Share siteSettings to all views
        View::composer('*', function ($view) {
            $view->with('siteSettings', app('siteSettings'));
        });
    }
}
