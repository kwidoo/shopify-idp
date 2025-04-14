<?php

namespace App\Providers;

use App\Contracts\ImpersonationLogRepository;
use App\Contracts\TokenServiceInterface;
use App\Repositories\ImpersonationLogRepositoryEloquent;
use App\Services\TokenService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TokenServiceInterface::class, TokenService::class);
        $this->app->bind(ImpersonationLogRepository::class, ImpersonationLogRepositoryEloquent::class);
        $this->app->bind(\App\Contracts\UserRepository::class, \App\Repositories\UserRepositoryEloquent::class);
        $this->app->bind(\App\Contracts\OIDCClientServiceInterface::class, \App\Services\OIDCClientService::class);
        $this->app->bind(\App\Contracts\UserProvisioningServiceInterface::class, \App\Services\ShopifyUserProvisioningService::class);
        $this->app->singleton(\App\Services\ShopifyWebhookService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);
    }
}
