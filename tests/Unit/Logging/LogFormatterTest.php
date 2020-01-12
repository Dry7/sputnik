<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use Carbon\Carbon;
use Monolog\Logger;
use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Logging\LogFormatter;
use Tests\TestCase;

class LogFormatterTest extends TestCase
{
    private LogFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new LogFormatter();

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
                    'level' => Logger::INFO,
                ],
                'expected' => '{"time":"2013-03-01T13:15:09Z","type":"info","message":"No matching handler found"}' . "\n",
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
                    'level' => Logger::INFO,
                ],
                'expected' => '{"time":"2013-03-01T13:15:09Z","type":"info","message":"No matching handler found {"post":1,"html":"<html></html>","array":{"string":"str"}}"}' . "\n",
            ],
            [
                'record' => [
                    'message' => 'Invalid operation: timeout',
                    'context' => [
                        'exception' => InvalidOperation::timeout(['new'])
                    ],
                    'level' => Logger::INFO,
                ],
                'expected' => '{"time":"2013-03-01T13:15:09Z","type":"info","message":"Invalid operation: timeout ["new"]"}' . "\n",
            ],
        ];
    }

    /**
     * @dataProvider formatDataProvider
     *
     * @param array $record
     * @param string $expected
     */
    public function testFormat(array $record, string $expected): void
    {
        $this->assertLogEquals($expected, $this->formatter->format($record));
    }

    public function testFormatWarning(): void
    {
        $record = [
            'message' => 'No matching handler found',
            'context' => [],
            'level' => Logger::WARNING,
        ];

        $this->assertSame('', $this->formatter->format($record));
    }

    public function testFormatBatch(): void
    {
        $records = [
            [
                'message' => 'Exception 1',
                'context' => [],
                'level' => Logger::INFO,
            ],
            [
                'message' => 'Exception 2',
                'context' => [],
                'level' => Logger::INFO,
            ],
        ];
        $expected = [
            '{"time":"2013-03-01T13:15:09Z","type":"info","message":"Exception 1"}' . "\n",
            '{"time":"2013-03-01T13:15:09Z","type":"info","message":"Exception 2"}' . "\n",
        ];

        self::assertSame($expected, $this->formatter->formatBatch($records));
    }
}
