<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use Carbon\Carbon;
use Monolog\Logger;
use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Logging\ErrorFormatter;
use Sputnik\Logging\LogFormatter;
use Sputnik\Logging\TelemetryFormatter;
use Tests\TestCase;
use DateTime;

class TelemetryFormatterTest extends TestCase
{
    /** @var ErrorFormatter */
    private $formatter;

    public function setUp(): void
    {
        $this->formatter = new TelemetryFormatter();

        Carbon::setTestNow('2013-03-01T16:15:09+03:00');

        parent::setUp();
    }

    public static function formatDataProvider()
    {
        return [
            [
                'record' => [
                    'message' => 'orientationAzimuthAngleDeg=0&orientationZenithAngleDeg=180&vesselAltitudeM=0&vesselSpeedMps=0&mainEngineFuelPct=0&temperatureInternalDeg=0'
                ],
                'expected' => '{"type":"values","timestamp":1362143709,"message":"orientationAzimuthAngleDeg=0&orientationZenithAngleDeg=180&vesselAltitudeM=0&vesselSpeedMps=0&mainEngineFuelPct=0&temperatureInternalDeg=0"}' . "\n",
            ],
        ];
    }

    /**
     * @dataProvider formatDataProvider
     *
     * @param array $record
     * @param string $expected
     */
    public function testFormat(array $record, string $expected)
    {
        $this->assertSame($expected, $this->formatter->format($record));
    }

    public function testFormatBatch()
    {
        $records = [
            [
                'message' => 'orientationAzimuthAngleDeg=0&orientationZenithAngleDeg=180&vesselAltitudeM=0&vesselSpeedMps=0&mainEngineFuelPct=0&temperatureInternalDeg=0',
            ],
            [
                'message' => 'orientationAzimuthAngleDeg=0&orientationZenithAngleDeg=180&vesselAltitudeM=0&vesselSpeedMps=0&mainEngineFuelPct=0&temperatureInternalDeg=0',
            ],
        ];
        $expected = [
            '{"type":"values","timestamp":1362143709,"message":"orientationAzimuthAngleDeg=0&orientationZenithAngleDeg=180&vesselAltitudeM=0&vesselSpeedMps=0&mainEngineFuelPct=0&temperatureInternalDeg=0"}' . "\n",
            '{"type":"values","timestamp":1362143709,"message":"orientationAzimuthAngleDeg=0&orientationZenithAngleDeg=180&vesselAltitudeM=0&vesselSpeedMps=0&mainEngineFuelPct=0&temperatureInternalDeg=0"}' . "\n",
        ];

        self::assertSame($expected, $this->formatter->formatBatch($records));
    }
}
