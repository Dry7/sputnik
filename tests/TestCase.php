<?php

namespace Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Iterator;
use Sputnik\Helpers\Utils;
use Sputnik\Models\Events\Event;
use Sputnik\Models\Operations\Operation;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function readFixture(string $fileName): string
    {
        return file_get_contents(base_path('tests/data/' . $fileName));
    }

    protected function iterator2array(Iterator $iterator): array
    {
        $result = [];

        foreach ($iterator as $item) {
            $result[] = $item;
        }

        return $result;
    }

    protected static function mockHttpHistory(...$queue): Collection
    {
        $history = collect();
        $stack = app(HandlerStack::class);
        $stack->setHandler(new MockHandler($queue));
        $stack->push(Middleware::history($history));

        return $history;
    }

    protected static function assertLogEquals(string $expected, string $actual)
    {
        self::assertEquals(
            self::clearRandomElements(self::clearSlashes($expected)),
            self::clearRandomElements(self::clearSlashes($actual))
        );
    }

    protected static function assertEqualsArray(array $expected, array $actual)
    {
        self::assertEquals(
            self::array2json($expected),
            self::array2json($actual)
        );
    }

    protected static function createOperation(): Operation
    {
        return Operation::createOperation(
            1,
            2,
            Operation::ORIENTATION_AZIMUTH_ANGLE_DEG,
            1,
            1
        );
    }

    protected static function createEvent(string $type = Event::TYPE_START_OPERATION): Event
    {
        return Event::createEvent(1542014400, $type, self::createOperation());
    }

    private static function clearRandomElements(string $text, array $elements = ['total_time', 'namelookup_time', 'total_time_us'])
    {
        foreach ($elements as $element) {
            $text = preg_replace('#"' . $element . '":([^,}]+)(,|})#i', '"' . $element . '":[RANDOM]$2', $text);
        }

        return $text;
    }

    private static function clearSlashes(string $html)
    {
        $html = preg_replace('~\\\\{2,}~', '\\', $html);
        return preg_replace('~\\\\"~', '"', $html);
    }

    private static function array2json(array $items)
    {
        return Utils::json(array_map(function ($item) { return (string)$item; }, $items));
    }
}
