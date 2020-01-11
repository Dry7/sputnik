<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Log;
use Sputnik\Services\ExchangeService;
use Sputnik\Services\FlightProgramService;
use Sputnik\Services\TerminateService;
use Sputnik\Services\TimeService;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function testHandlerStack(): void
    {
        // act
        $instance = app(HandlerStack::class);

        // assert
        $this->assertInstanceOf(HandlerStack::class, $instance);
    }

    public function testClient(): void
    {
        // act
        $instance = app(Client::class);

        // assert
        $this->assertInstanceOf(Client::class, $instance);
    }

    public function testClientOnStat(): void
    {
        // arrange
        $stats = ['hits' => 0];
        $transferStats = new TransferStats(
            new Request(\Illuminate\Http\Request::METHOD_GET, 'https://ya.ru'),
            null,
            null,
            null,
            $stats
        );

        // assert
        Log::shouldReceive('info')->with('Request https://ya.ru', $stats)->once();

        // act
        app(Client::class)->getConfig('on_stats')($transferStats);
    }

    public function testExchangeService(): void
    {
        // act
        $instance = app(ExchangeService::class);

        // assert
        $this->assertInstanceOf(ExchangeService::class, $instance);
    }

    public function testFlightProgramService(): void
    {
        // act
        $instance = app(FlightProgramService::class);

        // assert
        $this->assertInstanceOf(FlightProgramService::class, $instance);
    }

    public function testTimeService(): void
    {
        // act
        $instance = app(TimeService::class);

        // assert
        $this->assertInstanceOf(TimeService::class, $instance);
    }

    public function testTerminateService(): void
    {
        // act
        $instance = app(TerminateService::class);

        // assert
        $this->assertInstanceOf(TerminateService::class, $instance);
    }
}
