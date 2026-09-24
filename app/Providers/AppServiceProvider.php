<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\SesiMajlis;
use App\Observers\SesiMajlisObserver;
use App\Services\EventModeService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EventModeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SesiMajlis::observe(SesiMajlisObserver::class);

        View::addNamespace('user', resource_path('user'));
        View::addNamespace('media', resource_path('media'));
        View::addNamespace('admin', resource_path('admin'));

        View::composer('*', function ($view): void {
            $mode = $this->app->make(EventModeService::class)->current();

            $view->with('eventMode', $mode)
                ->with('isJasamu', $mode === EventModeService::MODE_JASAMU);
        });
    }
}
