<?php

namespace Sputnik\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Sputnik\Services\ExchangeService;
use Sputnik\Services\FlightProgramService;
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
        $guzzleHandleStack = function () {
            return HandlerStack::create();
        };

        if ($this->app->runningUnitTests()) {
            $this->app->singleton(HandlerStack::class, $guzzleHandleStack);
        } else {
            $this->app->bind(HandlerStack::class, $guzzleHandleStack);
        }

        $this->app->singleton(Client::class, function () {
            return new Client([
                'handler' => app(HandlerStack::class),
                'on_stats' => function (TransferStats $stats) {
                    Log::info('Request ' . $stats->getEffectiveUri(), $stats->getHandlerStats());
                }
            ]);
        });
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
    }
}
