<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Logging\ErrorFormatter;
use Tests\TestCase;
use DateTime;

class ErrorFormatterTest extends TestCase
{
    /** @var ErrorFormatter */
    private $formatter;

    public function setUp(): void
    {
        $this->formatter = new ErrorFormatter();
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
                ],
                'expected' => "\n" . '{"type":"error","timestamp":1362143709,"message":"No matching handler found Array\n(\n)\n"}',
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
                ],
                'expected' => "\n" . '{"type":"error","timestamp":1362575709,"message":"No matching handler found Array\n(\n    [post] => 1\n    [html] => <html><\/html>\n    [array] => Array\n        (\n            [string] => str\n        )\n\n)\n"}',
            ],
            [
                'record' => [
                    'message' => 'Invalid operation: critical',
                    'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                    'context' => [
                        'exception' => InvalidOperation::critical(['new'])
                    ],
                ],
                'expected' => "\n" . '{"type":"error","timestamp":1362143709,"message":"Invalid operation: critical Array\n(\n    [0] => new\n)\n"}',
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
                'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                'context' => [],
            ],
            [
                'message' => 'Exception 2',
                'datetime' => new DateTime('2013-03-01T16:15:09+03:00'),
                'context' => [],
            ],
        ];
        $expected = [
            "\n" . '{"type":"error","timestamp":1362143709,"message":"Exception 1 Array\n(\n)\n"}',
            "\n" . '{"type":"error","timestamp":1362143709,"message":"Exception 2 Array\n(\n)\n"}'
        ];

        self::assertSame($expected, $this->formatter->formatBatch($records));
    }
}
