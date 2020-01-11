<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Date;
use Mockery\Mock;
use Sputnik\Exceptions\InvalidCheck;
use Sputnik\Exceptions\InvalidFlightProgram;
use Sputnik\Exceptions\RequestException;
use Sputnik\Models\Operations\Operation;
use Sputnik\Services\ExchangeService;
use Sputnik\Services\FlightProgramService;
use Sputnik\Services\TelemetryService;
use Sputnik\Services\TimeService;
use Tests\TestCase;
use Mockery;

class FlightProgramServiceTest extends TestCase
{
    private const TELEMETRY_FREQUENCY = 10;

    /** @var FlightProgramService */
    private $service;

    /** @var TelemetryService|Mock */
    private $telemetryService;

    /** @var ExchangeService|Mock */
    private $exchangeService;

    /** @var TimeService|Mock */
    private $timeService;

    /** @var LogManager|Mock */
    private $logger;

    /** @var object */
    private $telemetryRequest;

    /** @var object */
    private $telemetryResponse;

    public function setUp(): void
    {
        $this->telemetryService = Mockery::mock(TelemetryService::class);
        $this->exchangeService = Mockery::mock(ExchangeService::class);
        $this->timeService = Mockery::mock(TimeService::class);
        $this->logger = Mockery::mock(LogManager::class);

        app()->instance(ExchangeService::class, $this->exchangeService);

        $this->service = new FlightProgramService(
            $this->telemetryService,
            $this->exchangeService,
            $this->timeService,
            $this->logger,
            self::TELEMETRY_FREQUENCY
        );

        $this->telemetryRequest = [
            'orientationAzimuthAngleDeg' => 20,
            'orientationZenithAngleDeg' => 20,
            'vesselAltitudeM' => 20,
            'vesselSpeedMps' => 20,
            'mainEngineFuelPct' => 20,
            'temperatureInternalDeg' => 20,
        ];

        $this->telemetryResponse = (object)[
            'orientationAzimuthAngleDeg' => (object)['set' => 20, 'value' => 20],
            'orientationZenithAngleDeg' => (object)['set' => 20, 'value' => 20],
            'vesselAltitudeM' => (object)['set' => 20, 'value' => 20],
            'vesselSpeedMps' => (object)['set' => 20, 'value' => 20],
            'mainEngineFuelPct' => (object)['set' => 20, 'value' => 20],
            'temperatureInternalDeg' => (object)['set' => 20, 'value' => 20],
        ];

        parent::setUp();
    }

    public static function loadDataProvider()
    {
        return [
            [
                'tests/data/flight_program/default.json',
                1555016400,
                [
                    (string)Operation::createOperation(1, 0, "coolingSystemPowerPct", 30, 2),
                    (string)Operation::createOperation(2, 10, "radioPowerDbm", 50, 1, false),
                    (string)Operation::createOperation(3, 15, "orientationZenithAngleDeg", 270, 10),
                    (string)Operation::createOperation(4, 15, "orientationAzimuthAngleDeg", 0, 10),
                ],
            ],
            [
                'tests/data/flight_program/empty.json',
                1554066000,
                [],
            ],
        ];
    }

    /**
     * @dataProvider loadDataProvider
     *
     * @param string $fileName
     * @param int $startUp
     * @param array $operations
     */
    public function testLoad(string $fileName, int $startUp, array $operations)
    {
        $flightProgram = $this->service->load($fileName);

        self::assertEquals($startUp, $flightProgram->getStartUp());
        self::assertEqualsArray($operations, $this->iterator2array($flightProgram->getOperations()));
    }

    public static function loadInvalidDataProvider()
    {
        return [
            [
                '/tmp/not_found',
                'Invalid flight program: file not found',
            ],
            [
                'tests/data',
                'Invalid flight program: not file',
            ],
        ];
    }

    /**
     * @dataProvider loadInvalidDataProvider
     *
     * @param string $fileName
     * @param string $expectedMessage
     */
    public function testLoadInvalid(string $fileName, string $expectedMessage)
    {
        self::expectException(InvalidFlightProgram::class);
        self::expectExceptionMessage($expectedMessage);

        $this->service->load($fileName);
    }

    public function testLoadPermissionDenied()
    {
        $fileName = 'tests/data/flight_program/permission_denied.json';

        self::expectException(InvalidFlightProgram::class);
        self::expectExceptionMessage('Invalid flight program: permission denied');

        try {
            chmod($fileName, 0333);
            $this->service->load($fileName);
        } finally {
            chmod($fileName, 0755);
        }
    }

    public function testRunEmpty()
    {
        Carbon::setTestNow('2019-04-01 00:00:00');

        $flightProgram = $this->service->load('tests/data/flight_program/empty.json');

        $this->logger->shouldReceive('info')->with('Start time: 1554076800')->once();
        $this->logger->shouldReceive('info')->with('End time: 1554076800')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1554076800')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '', 'isTelemetry' => true])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(TelemetryService::OPERATIONS)
            ->andReturn($this->telemetryResponse)
            ->once();
        $this->telemetryService->shouldReceive('send')->with($this->telemetryRequest)->once();
        $this->timeService->shouldReceive('sleep')->with(1)->once();

        Date::shouldReceive('now')->andReturn(
            Carbon::create(2019, 4, 1, 0, 0, 0),
            Carbon::create(2019, 4, 1, 0, 0, 0),
            Carbon::create(2019, 4, 1, 0, 0, 0),
            Carbon::create(2019, 4, 1, 0, 0, 1)
        );

        $this->service->run($flightProgram);
    }

    public function testRunOneRequest()
    {
        app()->instance(ExchangeService::class, $this->exchangeService);

        $flightProgram = $this->service->load('tests/data/flight_program/one_request.json');

        Date::shouldReceive('now')->andReturn(
            Carbon::createFromTimestamp(1554076800),
            Carbon::createFromTimestamp(1554076800),
            Carbon::createFromTimestamp(1554076800),
            Carbon::createFromTimestamp(1554076801),
            Carbon::createFromTimestamp(1554076802),
            Carbon::createFromTimestamp(1554076803),
            Carbon::createFromTimestamp(1554076804),
            Carbon::createFromTimestamp(1554076805)
        );

        $this->logger->shouldReceive('info')->with('Start time: 1554076800')->once();
        $this->logger->shouldReceive('info')->with('End time: 1554076804')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1554076800')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '', 'isTelemetry' => true])
            ->once();
        $this->logger->shouldReceive('info')->with('Current time: 1554076801')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1554076802')->once();
        $this->logger->shouldReceive('info')->with('Execute Starts: ', ['events' => '1'])->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(TelemetryService::OPERATIONS)
            ->andReturn($this->telemetryResponse)
            ->once();
        $this->telemetryService->shouldReceive('send')->with($this->telemetryRequest)->once();
        $this->timeService->shouldReceive('sleep')->with(1)->times(5);
        $this->exchangeService
            ->shouldReceive('patch')
            ->with([
                'coolingSystemPowerPct' => 30,
            ])
            ->andReturn((object)[
                'coolingSystemPowerPct' => (object)['set' => 30, 'value' => 20],
            ])
            ->once();

        $this->logger->shouldReceive('info')->with('Current time: 1554076803')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1554076804')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '1', 'isTelemetry' => false])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with([
                'coolingSystemPowerPct',
            ])
            ->andReturn((object)[
                'coolingSystemPowerPct' => (object)['set' => 30, 'value' => 30],
            ])
            ->once();

        $this->service->run($flightProgram);
    }

    public function testRunTwoRequestsInOneSecond()
    {
        app()->instance(ExchangeService::class, $this->exchangeService);

        $flightProgram = $this->service->load('tests/data/flight_program/two_request_in_one_second.json');

        Date::shouldReceive('now')->andReturn(
            Carbon::createFromTimestamp(1555016400),
            Carbon::createFromTimestamp(1555016400),
            Carbon::createFromTimestamp(1555016400),
            Carbon::createFromTimestamp(1555016401),
            Carbon::createFromTimestamp(1555016402),
            Carbon::createFromTimestamp(1555016403),
            Carbon::createFromTimestamp(1555016404)
        );

        $this->logger->shouldReceive('info')->with('Start time: 1555016400')->once();
        $this->logger->shouldReceive('info')->with('End time: 1555016403')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1555016400')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '', 'isTelemetry' => true])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(TelemetryService::OPERATIONS)
            ->andReturn($this->telemetryResponse)
            ->once();
        $this->telemetryService->shouldReceive('send')->with($this->telemetryRequest)->once();
        $this->logger->shouldReceive('info')->with('Current time: 1555016401')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute Starts: ', ['events' => '1, 2'])
            ->once();
        $this->timeService->shouldReceive('sleep')->with(1)->times(4);
        $this->exchangeService
            ->shouldReceive('patch')
            ->with(['coolingSystemPowerPct' => 30, 'radioPowerDbm' => 50])
            ->andReturn((object)[
                'coolingSystemPowerPct' => (object)['set' => 30, 'value' => 20],
                'radioPowerDbm' => (object)['set' => 50, 'value' => 50],
            ])
            ->once();

        $this->logger->shouldReceive('info')->with('Current time: 1555016402')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1555016403')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '1, 2', 'isTelemetry' => false])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(['coolingSystemPowerPct', 'radioPowerDbm'])
            ->andReturn((object)[
                'coolingSystemPowerPct' => (object)['set' => 30, 'value' => 30],
                'radioPowerDbm' => (object)['set' => 50, 'value' => 50],
            ])
            ->once();

        $this->service->run($flightProgram);
    }

    public function testRunTwoRequestsWithOneVariable()
    {
        // assert
        self::expectException(InvalidCheck::class);

        app()->instance(ExchangeService::class, $this->exchangeService);

        $flightProgram = $this->service->load('tests/data/flight_program/two_requests_with_one_variable.json');

        Date::shouldReceive('now')->andReturn(
            Carbon::createFromTimestamp(1555016400),
            Carbon::createFromTimestamp(1555016400),
            Carbon::createFromTimestamp(1555016400),
            Carbon::createFromTimestamp(1555016401),
            Carbon::createFromTimestamp(1555016402),
            Carbon::createFromTimestamp(1555016403),
            Carbon::createFromTimestamp(1555016404)
        );

        $this->logger->shouldReceive('info')->with('Start time: 1555016400')->once();
        $this->logger->shouldReceive('info')->with('End time: 1555016402')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1555016400')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '', 'isTelemetry' => true])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(TelemetryService::OPERATIONS)
            ->andReturn($this->telemetryResponse)
            ->once();
        $this->logger->shouldReceive('info')
            ->with('Execute Starts: ', ['events' => '1, 2'])
            ->once();
        $this->exchangeService
            ->shouldReceive('patch')
            ->with(['orientationZenithAngleDeg' => 0])
            ->andReturn((object)['orientationZenithAngleDeg' => (object)['set' => 0, 'value' => 0]])
            ->once();
        $this->exchangeService
            ->shouldReceive('patch')
            ->with(['orientationZenithAngleDeg' => 2])
            ->andReturn((object)['orientationZenithAngleDeg' => (object)['set' => 2, 'value' => 2]])
            ->once();
        $this->telemetryService->shouldReceive('send')->with($this->telemetryRequest)->once();
        $this->timeService->shouldReceive('sleep')->with(1)->times(2);
        $this->logger->shouldReceive('info')->with('Current time: 1555016401')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1555016402')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '1, 2', 'isTelemetry' => false])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(['orientationZenithAngleDeg'])
            ->andReturn((object)[
                'orientationZenithAngleDeg' => (object)['set' => 2, 'value' => 2],
            ])
            ->once();

        $this->service->run($flightProgram);
    }

    public function testRunExchangeServiceGetException()
    {
        // assert
        self::expectException(InvalidCheck::class);

        Carbon::setTestNow('2019-04-01 00:00:00');

        $flightProgram = $this->service->load('tests/data/flight_program/empty.json');

        $this->logger->shouldReceive('info')->with('Start time: 1554076800')->once();
        $this->logger->shouldReceive('info')->with('End time: 1554076800')->once();
        $this->logger->shouldReceive('info')->with('Current time: 1554076800')->once();
        $this->logger->shouldReceive('info')
            ->with('Execute checks: ', ['events' => '', 'isTelemetry' => true])
            ->once();
        $this->exchangeService
            ->shouldReceive('get')
            ->with(TelemetryService::OPERATIONS)
            ->andThrow(RequestException::timeout())
            ->once();

        Date::shouldReceive('now')->andReturn(
            Carbon::create(2019, 4, 1, 0, 0, 0),
            Carbon::create(2019, 4, 1, 0, 0, 0)
        );

        $this->service->run($flightProgram);
    }
}
