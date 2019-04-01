<?php

namespace Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Iterator;
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

    protected function mockHttpHistory(...$queue): Collection
    {
        $history = collect();
        $stack = app(HandlerStack::class);
        $stack->setHandler(new MockHandler($queue));
        $stack->push(Middleware::history($history));

        return $history;
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

    protected static function createEvent(): Event
    {
        return Event::createEvent(1542014400, Event::TYPE_START_OPERATION, self::createOperation());
    }
}
