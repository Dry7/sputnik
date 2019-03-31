<?php

namespace Sputnik\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Sputnik\Services\ExchangeService;
use Sputnik\Services\FlightProgramService;
use Sputnik\Services\LoggerService;
use Sputnik\Services\TelemetryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ExchangeService::class, function ($app) {
            return new ExchangeService(
                $app[Client::class],
                config('sputnik.exchange_uri'),
                config('sputnik.exchange_timeout')
            );
        });
        $this->app->singleton(FlightProgramService::class, function ($app) {
            return new FlightProgramService(
                $app[TelemetryService::class],
                $app[ExchangeService::class],
                config('sputnik.telemetry_freq')
            );
        });
        $this->app->singleton(LoggerService::class);
    }
}
