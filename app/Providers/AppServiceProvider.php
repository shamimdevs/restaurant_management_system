<?php

namespace App\Providers;

use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\LoyaltyService;
use App\Services\OrderService;
use App\Services\ReportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(LoyaltyService::class);
        $this->app->singleton(AccountingService::class);
        $this->app->singleton(ReportService::class);

        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService(
                $app->make(InventoryService::class),
                $app->make(LoyaltyService::class),
                $app->make(AccountingService::class),
            );
        });
    }

    public function boot(): void
    {
        // Strict mode catches lazy loading, mass assignment, etc. in development
        \Illuminate\Database\Eloquent\Model::shouldBeStrict(! app()->isProduction());
    }
}
