<?php

declare(strict_types=1);

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
        'invalid_patch_response' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                'not json'
            ],
        ],
        'invalid_wrong_response' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":20,"value":20}}'
            ],
        ],
        'wrong_patch_not_critical' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":20,"value":20}}',
                '{"coolingSystemPowerPct":{"set":20,"value":30}}',
                '{"radioPowerDbm":{"set":50,"value":50}}',
                '{"radioPowerDbm":{"set":50,"value":50}}',
            ],
        ],
        'invalid_wrong_type_response' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":30,"value":"test"}}'
            ],
        ],
        'failed_check_one_request' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":30,"value":30}}',
                '{"coolingSystemPowerPct":{"set":30,"value":20}}',
            ],
        ],
        'failed_check_one_request_type' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":30,"value":30}}',
                '{"coolingSystemPowerPct":{"set":30,"value":"test"}}',
            ],
        ],
        'failed_check_one_request_invalid_json' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":30,"value":30}}',
                'sdf',
            ],
        ],
        'two_request_in_one_second' => [
            'date' => '2019-04-11 21:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":30,"value":30},"radioPowerDbm":{"set":50,"value":50}}',
                '{"coolingSystemPowerPct":{"set":30,"value":30},"radioPowerDbm":{"set":50,"value":50}}',
            ],
        ],
        'telemetry_every_second' => [
            'date' => '2019-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                self::TELEMETRY_RESPONSE,
                self::TELEMETRY_RESPONSE,
                '{"coolingSystemPowerPct":{"set":30,"value":30}}',
                self::TELEMETRY_RESPONSE,
                '{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5},"coolingSystemPowerPct":{"set":30,"value":30}}',
            ],
        ],
        'overpass' => [
            'date' => '2020-04-01 00:00:00',
            'requests' => [
                self::TELEMETRY_RESPONSE
            ],
        ],
        'overpass_part' => [
            'date' => '2019-04-11 21:00:15',
            'requests' => [
                self::TELEMETRY_RESPONSE,
                '{"orientationZenithAngleDeg":{"set":270,"value":270},"orientationAzimuthAngleDeg":{"set":0,"value":0}}',
                '{"orientationZenithAngleDeg":{"set":270,"value":270},"orientationAzimuthAngleDeg":{"set":0,"value":0}}',
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

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: /tmp/not_existing_file.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"TerminateService::exit {"code":10}"}

EOF
            , $process->getOutput());
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076800,"message":"Invalid flight program: file not found {"fileName":"/tmp/not_existing_file.json"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(10, $process->getExitCode());
    }

    public function testEmptyOperations()
    {
        $process = $this->createProcess('empty_operations', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/empty.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/empty.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}

EOF
            ,
            $process->getOutput()
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

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/empty.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg {"url":"https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg","content_type":null,"http_code":0,"header_size":0,"request_size":0,"filetime":-1,"ssl_verify_result":0,"redirect_count":0,"total_time":0.0001,"namelookup_time":3.3e-5,"connect_time":0,"pretransfer_time":0,"size_upload":0,"size_download":0,"speed_download":0,"speed_upload":0,"download_content_length":-1,"upload_content_length":-1,"starttransfer_time":0,"redirect_time":0,"redirect_url":"","primary_ip":"","certinfo":[],"primary_port":0,"local_ip":"","local_port":0,"http_version":0,"protocol":0,"ssl_verifyresult":0,"scheme":"","appconnect_time_us":0,"connect_time_us":0,"namelookup_time_us":0,"pretransfer_time_us":0,"redirect_time_us":0,"starttransfer_time_us":0,"total_time_us":99578}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"TerminateService::exit {"code":12}"}

EOF
            ,
            $process->getOutput()
        );
        self::assertLogEquals(
            <<<EOF
{"type":"error","timestamp":1554076800,"message":"Invalid exchangeService {"message":"Request timeout","context":{"method":"GET","url":"https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg","options":{"timeout":0.1}}}"}

EOF
            ,
            $process->getErrorOutput()
        );
        self::assertEquals(12, $process->getExitCode());
    }

    public function testInvalidPatchResponse()
    {
        $process = $this->createProcess('invalid_patch_response', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"not json"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"TerminateService::exit {"code":11}"}

EOF
            ,
            $process->getOutput()
        );
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076802,"message":"ExchangeService: invalid json {"html":"not json"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(11, $process->getExitCode());
    }

    public function testWrongPatchResponse()
    {
        $process = $this->createProcess('invalid_wrong_response', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":20,"value":20}}"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"TerminateService::exit {"code":11}"}

EOF
            ,
            $process->getOutput()
        );
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076802,"message":"Event: failed check {"event":"{"class":"Sputnik\Models\Events\StartOperationEvent","type":"start_operation","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":true}"}","data":"{"coolingSystemPowerPct":{"set":20,"value":20}}"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(11, $process->getExitCode());
    }

    public function testWrongPatchResponseNotCritical()
    {
        $process = $this->createProcess('wrong_patch_not_critical', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/wrong_patch_not_critical.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/wrong_patch_not_critical.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076806"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":20,"value":20}}"}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Current time: 1554076803"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Current time: 1554076804"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Execute checks:  {"events":"1","isTelemetry":false}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct"]"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":20,"value":30}}"}"}
{"time":"2019-04-01T00:00:05Z","type":"info","message":"Current time: 1554076805"}
{"time":"2019-04-01T00:00:05Z","type":"info","message":"Execute Starts:  {"events":"2"}"}
{"time":"2019-04-01T00:00:05Z","type":"info","message":"ExchangeService::patch {"radioPowerDbm":50}"}
{"time":"2019-04-01T00:00:05Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:05Z","type":"info","message":"ExchangeService::parseResult {"html":"{"radioPowerDbm":{"set":50,"value":50}}"}"}
{"time":"2019-04-01T00:00:06Z","type":"info","message":"Current time: 1554076806"}
{"time":"2019-04-01T00:00:06Z","type":"info","message":"Execute checks:  {"events":"2","isTelemetry":false}"}
{"time":"2019-04-01T00:00:06Z","type":"info","message":"ExchangeService::get ["radioPowerDbm"]"}
{"time":"2019-04-01T00:00:06Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/radioPowerDbm"}
{"time":"2019-04-01T00:00:06Z","type":"info","message":"ExchangeService::parseResult {"html":"{"radioPowerDbm":{"set":50,"value":50}}"}"}

EOF
            ,
            $process->getOutput()
        );

        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076802,"message":"Event: failed check {"event":"{"class":"Sputnik\Models\Events\StartOperationEvent","type":"start_operation","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":false}"}","data":"{"coolingSystemPowerPct":{"set":20,"value":20}}"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    public function testWrongTypePatchResponse()
    {
        $process = $this->createProcess('invalid_wrong_type_response', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":"test"}}"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"TerminateService::exit {"code":11}"}

EOF
            ,
            $process->getOutput()
        );
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076802,"message":"Event: failed check {"type":"start_operation","event":"{"class":"Sputnik\Models\Events\StartOperationEvent","type":"start_operation","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":true}"}","data":"{"coolingSystemPowerPct":{"set":30,"value":"test"}}"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(11, $process->getExitCode());
    }

    public function testFailedCheckOnOneRequest()
    {
        $process = $this->createProcess('failed_check_one_request', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30}}"}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Current time: 1554076803"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Current time: 1554076804"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Execute checks:  {"events":"1","isTelemetry":false}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct"]"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":20}}"}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"TerminateService::exit {"code":12}"}

EOF
            , $process->getOutput());
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076804,"message":"Invalid check: value {"event":"{"class":"Sputnik\Models\Events\CheckOperationResultsEvent","type":"check_operation_results","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":true}"}","data":"{"coolingSystemPowerPct":{"set":30,"value":20}}"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(12, $process->getExitCode());
    }

    public function testFailedCheckOnOneRequestNotCritical()
    {
        $process = $this->createProcess('failed_check_one_request', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request_not_critical.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request_not_critical.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30}}"}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Current time: 1554076803"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Current time: 1554076804"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Execute checks:  {"events":"1","isTelemetry":false}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct"]"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":20}}"}"}

EOF
            , $process->getOutput());
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076804,"message":"Invalid check: value {"event":"{"class":"Sputnik\Models\Events\CheckOperationResultsEvent","type":"check_operation_results","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":false}"}","data":"{"coolingSystemPowerPct":{"set":30,"value":20}}"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    public function testFailedCheckOnOneRequestType()
    {
        $process = $this->createProcess('failed_check_one_request_type', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30}}"}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Current time: 1554076803"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Current time: 1554076804"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Execute checks:  {"events":"1","isTelemetry":false}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct"]"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":"test"}}"}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"TerminateService::exit {"code":12}"}

EOF
            , $process->getOutput());
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076804,"message":"Invalid check: value {"type":"check_operation_results","event":"{"class":"Sputnik\Models\Events\CheckOperationResultsEvent","type":"check_operation_results","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":true}"}","data":"{"coolingSystemPowerPct":{"set":30,"value":"test"}}"}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(12, $process->getExitCode());
    }

    public function testFailedCheckOnOneRequestInvalidJson()
    {
        $process = $this->createProcess('failed_check_one_request_invalid_json', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30}}"}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Current time: 1554076803"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Current time: 1554076804"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Execute checks:  {"events":"1","isTelemetry":false}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct"]"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::parseResult {"html":"sdf"}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"TerminateService::exit {"code":12}"}

EOF
            , $process->getOutput());
        self::assertLogEquals(<<<EOF
{"type":"error","timestamp":1554076804,"message":"Invalid exchangeService {"event":"{"class":"Sputnik\Models\Events\CheckOperationResultsEvent","type":"check_operation_results","operation":"{"class":"Sputnik\Models\Operations\CoolingSystemPowerPctOperation","id":1,"deltaT":2,"variable":"coolingSystemPowerPct","value":30,"timeout":2,"critical":true}"}","message":"ExchangeService: invalid json","context":{"html":"sdf"}}"}

EOF
            , $process->getErrorOutput());
        self::assertEquals(12, $process->getExitCode());
    }

    public function testTwoRequests()
    {
        $process = $this->createProcess('two_request_in_one_second', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/two_request_in_one_second.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Filename: tests/data/flight_program/two_request_in_one_second.json"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Start time: 1555016400"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"End time: 1555016403"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Current time: 1555016400"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-11T21:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1555016400,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-11T21:00:01Z","type":"info","message":"Current time: 1555016401"}
{"time":"2019-04-11T21:00:01Z","type":"info","message":"Execute Starts:  {"events":"1, 2"}"}
{"time":"2019-04-11T21:00:01Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30,"radioPowerDbm":50}"}
{"time":"2019-04-11T21:00:01Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-11T21:00:01Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30},"radioPowerDbm":{"set":50,"value":50}}"}"}
{"time":"2019-04-11T21:00:02Z","type":"info","message":"Current time: 1555016402"}
{"time":"2019-04-11T21:00:03Z","type":"info","message":"Current time: 1555016403"}
{"time":"2019-04-11T21:00:03Z","type":"info","message":"Execute checks:  {"events":"1, 2","isTelemetry":false}"}
{"time":"2019-04-11T21:00:03Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct","radioPowerDbm"]"}
{"time":"2019-04-11T21:00:03Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct,radioPowerDbm"}
{"time":"2019-04-11T21:00:03Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30},"radioPowerDbm":{"set":50,"value":50}}"}"}

EOF,
            $process->getOutput()
        );
        self::assertLogEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    public function testTelemetryEverySecond()
    {
        $process = $this->createProcess('telemetry_every_second', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/one_request.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
            'TELEMETRY_FREQ' => 1,
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/one_request.json"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Start time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"End time: 1554076804"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Current time: 1554076800"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076800,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Current time: 1554076801"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:01Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076801,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Current time: 1554076802"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Execute Starts:  {"events":"1"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::patch {"coolingSystemPowerPct":30}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"ExchangeService::parseResult {"html":"{"coolingSystemPowerPct":{"set":30,"value":30}}"}"}
{"time":"2019-04-01T00:00:02Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076802,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Current time: 1554076803"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-01T00:00:03Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1554076803,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Current time: 1554076804"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Execute checks:  {"events":"1","isTelemetry":true}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::get ["coolingSystemPowerPct","orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/coolingSystemPowerPct,orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5},"coolingSystemPowerPct":{"set":30,"value":30}}"}"}
{"time":"2019-04-01T00:00:04Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5,"coolingSystemPowerPct":30}"}
{"type":"values","timestamp":1554076804,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}

EOF,
            $process->getOutput()
        );
        self::assertLogEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    public function testOverpassFlightProgram()
    {
        $process = $this->createProcess('overpass', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/default.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(
            <<<EOF
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Let`s go"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Filename: tests/data/flight_program/default.json"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Start time: 1585699200"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"End time: 1585699200"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Current time: 1585699200"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2020-04-01T00:00:00Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1585699200,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}

EOF
            ,
            $process->getOutput()
        );
        self::assertLogEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    public function testOverpassOnlyPart()
    {
        $process = $this->createProcess('overpass_part', [
            'FLIGHT_PROGRAM' => 'tests/data/flight_program/default.json',
            'EXCHANGE_URI' => 'https://exchange.internal/api/v12',
        ]);
        $process->run();

        self::assertLogEquals(<<<EOF
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Let`s go"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Filename: tests/data/flight_program/default.json"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Start time: 1555016415"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"End time: 1555016425"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Current time: 1555016415"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Execute checks:  {"events":"","isTelemetry":true}"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"ExchangeService::get ["orientationAzimuthAngleDeg","orientationZenithAngleDeg","vesselAltitudeM","vesselSpeedMps","mainEngineFuelPct","temperatureInternalDeg"]"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationAzimuthAngleDeg,orientationZenithAngleDeg,vesselAltitudeM,vesselSpeedMps,mainEngineFuelPct,temperatureInternalDeg"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationAzimuthAngleDeg":{"set":5,"value":5},"orientationZenithAngleDeg":{"set":185,"value":185},"vesselAltitudeM":{"set":5,"value":5},"vesselSpeedMps":{"set":5,"value":5},"mainEngineFuelPct":{"set":5,"value":5},"temperatureInternalDeg":{"set":5,"value":5}}"}"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Execute Starts:  {"events":"3, 4"}"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"ExchangeService::patch {"orientationZenithAngleDeg":270,"orientationAzimuthAngleDeg":0}"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Request https://exchange.internal/api/v12/settings"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationZenithAngleDeg":{"set":270,"value":270},"orientationAzimuthAngleDeg":{"set":0,"value":0}}"}"}
{"time":"2019-04-11T21:00:15Z","type":"info","message":"Telemetry::send {"orientationAzimuthAngleDeg":5,"orientationZenithAngleDeg":185,"vesselAltitudeM":5,"vesselSpeedMps":5,"mainEngineFuelPct":5,"temperatureInternalDeg":5}"}
{"type":"values","timestamp":1555016415,"message":"orientationAzimuthAngleDeg=5&orientationZenithAngleDeg=185&vesselAltitudeM=5&vesselSpeedMps=5&mainEngineFuelPct=5&temperatureInternalDeg=5"}
{"time":"2019-04-11T21:00:16Z","type":"info","message":"Current time: 1555016416"}
{"time":"2019-04-11T21:00:17Z","type":"info","message":"Current time: 1555016417"}
{"time":"2019-04-11T21:00:18Z","type":"info","message":"Current time: 1555016418"}
{"time":"2019-04-11T21:00:19Z","type":"info","message":"Current time: 1555016419"}
{"time":"2019-04-11T21:00:20Z","type":"info","message":"Current time: 1555016420"}
{"time":"2019-04-11T21:00:21Z","type":"info","message":"Current time: 1555016421"}
{"time":"2019-04-11T21:00:22Z","type":"info","message":"Current time: 1555016422"}
{"time":"2019-04-11T21:00:23Z","type":"info","message":"Current time: 1555016423"}
{"time":"2019-04-11T21:00:24Z","type":"info","message":"Current time: 1555016424"}
{"time":"2019-04-11T21:00:25Z","type":"info","message":"Current time: 1555016425"}
{"time":"2019-04-11T21:00:25Z","type":"info","message":"Execute checks:  {"events":"3, 4","isTelemetry":false}"}
{"time":"2019-04-11T21:00:25Z","type":"info","message":"ExchangeService::get ["orientationZenithAngleDeg","orientationAzimuthAngleDeg"]"}
{"time":"2019-04-11T21:00:25Z","type":"info","message":"Request https://exchange.internal/api/v12/settings/orientationZenithAngleDeg,orientationAzimuthAngleDeg"}
{"time":"2019-04-11T21:00:25Z","type":"info","message":"ExchangeService::parseResult {"html":"{"orientationZenithAngleDeg":{"set":270,"value":270},"orientationAzimuthAngleDeg":{"set":0,"value":0}}"}"}

EOF
            , $process->getOutput());
        self::assertLogEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
    }

    private function createProcess(string $test, array $env = null): Process
    {
        return new Process(
            ['php', 'artisan', 'sputnik:control-panel', '--test=' . $test],
            null,
            $env + ['APP_ENV' => 'testing', 'TELEMETRY_FREQ' => 20],
            null,
            self::TIMEOUT
        );
    }
}
