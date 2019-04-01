<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use Monolog\Logger;
use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Logging\ErrorFormatter;
use Sputnik\Logging\LogFormatter;
use Tests\TestCase;
use DateTime;

class LogFormatterTest extends TestCase
{
    /** @var ErrorFormatter */
    private $formatter;

    public function setUp(): void
    {
        $this->formatter = new LogFormatter();
        parent::setUp();
    }

    public static function formatDataProvider()
    {
        return [
            [
                'record' => [
                    'message' => 'No matching handler found',
                    'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                    'context' => [],
                    'level' => Logger::INFO,
                ],
                'expected' => "\n" . '{"time":"2013-03-01T13:15:09Z","type":"info","message":"No matching handler found"}',
            ],
            [
                'record' => [
                    'message' => 'No matching handler found',
                    'datetime' => new DateTime('2013-03-06T16:15:09+03:00'),
                    'context' => [
                        'post' => 1,
                        'html' => '<html></html>',
                        'array' => [
                            'string' => 'str',
                        ],
                    ],
                    'level' => Logger::INFO,
                ],
                'expected' => "\n" . '{"time":"2013-03-06T13:15:09Z","type":"info","message":"No matching handler found {\"post\":1,\"html\":\"<html><\\\\\\/html>\",\"array\":{\"string\":\"str\"}}"}',
            ],
            [
                'record' => [
                    'message' => 'Invalid operation: critical',
                    'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                    'context' => [
                        'exception' => InvalidOperation::critical(['new'])
                    ],
                    'level' => Logger::INFO,
                ],
                'expected' => "\n" . '{"time":"2013-03-01T13:15:09Z","type":"info","message":"Invalid operation: critical [\"new\"]"}',
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

    public function testFormatWarning()
    {
        $record = [
            'message' => 'No matching handler found',
            'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
            'context' => [],
            'level' => Logger::WARNING,
        ];

        $this->assertSame('', $this->formatter->format($record));
    }

    public function testFormatBatch()
    {
        $records = [
            [
                'message' => 'Exception 1',
                'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                'context' => [],
                'level' => Logger::INFO,
            ],
            [
                'message' => 'Exception 2',
                'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                'context' => [],
                'level' => Logger::INFO,
            ],
        ];
        $expected = [
            "\n" . '{"time":"2013-03-01T13:15:09Z","type":"info","message":"Exception 1"}',
            "\n" . '{"time":"2013-03-01T13:15:09Z","type":"info","message":"Exception 2"}',
        ];

        self::assertSame($expected, $this->formatter->formatBatch($records));
    }
}
