<?php

namespace Sputnik\Providers;

use Illuminate\Support\ServiceProvider;
use Sputnik\Services\FlightProgramService;
use Sputnik\Services\LoggerService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FlightProgramService::class);
        $this->app->singleton(LoggerService::class);
    }
}
