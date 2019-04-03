<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use Carbon\Carbon;
use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Logging\ErrorFormatter;
use Tests\TestCase;

class ErrorFormatterTest extends TestCase
{
    /** @var ErrorFormatter */
    private $formatter;

    public function setUp(): void
    {
        $this->formatter = new ErrorFormatter();

        Carbon::setTestNow('2013-03-01T16:15:09+03:00');

        parent::setUp();
    }

    public static function formatDataProvider()
    {
        return [
            [
                'record' => [
                    'message' => 'No matching handler found',
                    'context' => [],
                ],
                'expected' => '{"type":"error","timestamp":1362143709,"message":"No matching handler found"}' . "\n",
            ],
            [
                'record' => [
                    'message' => 'No matching handler found',
                    'context' => [
                        'post' => 1,
                        'html' => '<html></html>',
                        'array' => [
                            'string' => 'str',
                        ],
                    ],
                ],
                'expected' => '{"type":"error","timestamp":1362143709,"message":"No matching handler found {\"post\":1,\"html\":\"<html><\\\\\\/html>\",\"array\":{\"string\":\"str\"}}"}'. "\n",
            ],
            [
                'record' => [
                    'message' => 'Invalid operation: critical',
                    'context' => [
                        'exception' => InvalidOperation::critical(['new'])
                    ],
                ],
                'expected' => '{"type":"error","timestamp":1362143709,"message":"Invalid operation: critical [\"new\"]"}' . "\n",
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
                'message' => 'Exception 1',
                'context' => [],
            ],
            [
                'message' => 'Exception 2',
                'context' => [],
            ],
        ];
        $expected = [
           '{"type":"error","timestamp":1362143709,"message":"Exception 1"}' . "\n",
           '{"type":"error","timestamp":1362143709,"message":"Exception 2"}' . "\n",
        ];

        self::assertSame($expected, $this->formatter->formatBatch($records));
    }
}
