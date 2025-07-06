<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Repositories\ProjectRepository;
use App\Services\ProjectService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProjectRepository::class, ProjectRepository::class);
        $this->app->singleton(ProjectService::class, ProjectService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
