<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Log\LogManager;
use Sputnik\Services\TelemetryService;
use Tests\TestCase;
use Mockery;

class TelemetryServiceTest extends TestCase
{
    /** @var TelemetryService */
    private $service;

    /** @var LogManager|Mockery\Mock */
    private $logging;

    public function setUp(): void
    {
        $this->logging = Mockery::mock(LogManager::class);
        $this->service = new TelemetryService($this->logging);

        parent::setUp();
    }

    public function testSend()
    {
        // arrange
        $variables = [
            'orientationAzimuthAngleDeg' => 5,
            'orientationZenithAngleDeg' => 185,
            'vesselAltitudeM' => 5,
            'vesselSpeedMps' => 5,
            'mainEngineFuelPct' => 5,
            'temperatureInternalDeg' => 5,
        ];
        $message = 'orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5';

        // assert
        $this->logging->shouldReceive('info')->with("Telemetry::send", $variables);
        $this->logging->shouldReceive('channel')->with('telemetry')->andReturnUsing(static function () use ($message) {
            return Mockery::mock(LogManager::class)->shouldReceive('info')->with($message)->getMock();
        });

        // act
        $this->service->send($variables);
    }
}
