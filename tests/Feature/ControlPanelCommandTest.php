<?php

namespace Tests\Feature;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ControlPanelCommandTest extends TestCase
{
    private const TIMEOUT = 120;
    private const TELEMETRY_RESPONSE = '{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}';

    public const TEST_ENVIRONMENTS = [
        'file_not_found' => [
            'date' => '2019-04-01 00:00:00',
        ],
        'request_error' => [
            'date' => '2019-04-01 00:00:00',
        ],
        'empty_operations' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE
            ],
        ],
    ];

    public static function environment(string $test)
    {
        $data = self::TEST_ENVIRONMENTS[$test];

        Carbon::setTestNow($data['date']);

        $responses = collect($data['requests'] ?? [])->map(function ($html) {
            return new Response(HttpResponse::HTTP_OK, [], $html);
        })->toArray();

        if (!empty($responses)) {
            self::mockHttpHistory(...$responses);
        }
    }

    public function testFileNotFound()
    {
        $process = $this->createProcess('file_not_found', [
            'FLIGHT_PROGRAM' => '/tmp/not_existing_file.json',
        ]);
        $process->run();

        self::assertEquals(
            '{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}' . "\n"
                   . '{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: \/tmp\/not_existing_file.json"}' . "\n"
                   . '{"time":"2019-04-01T00:00:00Z","type":"info","message":"TerminateService::exit {\"code\":10}"}' . "\n",
            $process->getOutput()
        );
        self::assertEquals(
            '{"type":"error","timestamp":1554076800,"message":"Invalid flight program: file not found {\"fileName\":\"\\\\\\/tmp\\\\\\/not_existing_file.json\"}"}' . "\n",
            $process->getErrorOutput()
        );
        self::assertEquals(10, $process->getExitCode());
    }

    public function testEmptyOperations()
    {
        $process = $this->createProcess('empty_operations', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/empty.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests\/data\/flight_program\/empty.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {\"events\":\"\"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get [\"orientationAzimuthAngleDeg\",\"orientationZenithAngleDeg\",\"vesselAltitudeM\",\"vesselSpeedMps\",\"mainEngineFuelPct\",\"temperatureInternalDeg\"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https:\/\/exchange.internal\/api\/v12\/settings\/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {\"html\":\"{\\\\\\"orientationAzimuthAngleDeg\\\\\\":{\\\\\\"set\\\\\\":5,\\\\\\"value\\\\\\":5},\\\\\\"orientationZenithAngleDeg\\\\\\":{\\\\\\"set\\\\\\":185,\\\\\\"value\\\\\\":185},\\\\\\"vesselAltitudeM\\\\\\":{\\\\\\"set\\\\\\":5,\\\\\\"value\\\\\\":5},\\\\\\"vesselSpeedMps\\\\\\":{\\\\\\"set\\\\\\":5,\\\\\\"value\\\\\\":5},\\\\\\"mainEngineFuelPct\\\\\\":{\\\\\\"set\\\\\\":5,\\\\\\"value\\\\\\":5},\\\\\\"temperatureInternalDeg\\\\\\":{\\\\\\"set\\\\\\":5,\\\\\\"value\\\\\\":5}}\"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {\"orientationAzimuthAngleDeg\":5,\"orientationZenithAngleDeg\":185,\"vesselAltitudeM\":5,\"vesselSpeedMps\":5,\"mainEngineFuelPct\":5,\"temperatureInternalDeg\":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}

EOF
            , $process->getOutput()
        );
        self::assertLogEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    public function testRequestError()
    {
        $process = $this->createProcess('request_error', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/empty.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests\/data\/flight_program\/empty.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {\"events\":\"\"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get [\"orientationAzimuthAngleDeg\",\"orientationZenithAngleDeg\",\"vesselAltitudeM\",\"vesselSpeedMps\",\"mainEngineFuelPct\",\"temperatureInternalDeg\"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https:\/\/exchange.internal\/api\/v12\/settings\/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg {\"url\":\"https:\\\\\\/\\\\\\/exchange.internal\\\\\\/api\\\\\\/v12\\\\\\/settings\\\\\\/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg\",\"content_type\":null,\"http_code\":0,\"header_size\":0,\"request_size\":0,\"filetime\":-1,\"ssl_verify_result\":0,\"redirect_count\":0,\"total_time\":0.0001,\"namelookup_time\":3.3e-5,\"connect_time\":0,\"pretransfer_time\":0,\"size_upload\":0,\"size_download\":0,\"speed_download\":0,\"speed_upload\":0,\"download_content_length\":-1,\"upload_content_length\":-1,\"starttransfer_time\":0,\"redirect_time\":0,\"redirect_url\":\"\",\"primary_ip\":\"\",\"certinfo\":[],\"primary_port\":0,\"local_ip\":\"\",\"local_port\":0}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"TerminateService::exit {\"code\":11}"}

EOF
            , $process->getOutput()
        );
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076800,"message":"Request timeout {\"method\":\"GET\",\"url\":\"https:\\\\\\/\\\\\\/exchange.internal\\\\\\/api\\\\\\/v12\\\\\\/settings\\\\\\/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg\",\"options\":{\"timeout\":0.1}}"}

EOF
            , $process->getErrorOutput()
        );
        self::assertEquals(11, $process->getExitCode());
    }

    private function createProcess(string $test, array $env = null): Process
    {
        return new Process(
            ['php', 'artisan', 'sputnik:control-panel', '--test=' . $test],
            null,
            $env + ['APP_ENV' => 'testing'],
            null,
            self::TIMEOUT
        );
    }
}
