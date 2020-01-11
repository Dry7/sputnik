<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Sputnik\Services\TimeService;
use Tests\TestCase;

class TimeServiceTest extends TestCase
{
    public function testTesting()
    {
        // arrange
        $date = new Carbon();
        $service = new TimeService(true);

        // assert
        Date::shouldReceive('now')->andReturnUsing(static function () use ($date) {
            return Date::shouldReceive('addSeconds')->with(1)->andReturn($date)->getMock();
        });
        Date::shouldReceive('setTestNow')->with($date);

        // act
        $service->sleep(1);
    }

    public function testProduction()
    {
        // arrange
        $service = new TimeService(false);
        $start = now()->timestamp;

        // act
        $service->sleep(1);

        // assert
        self::assertEquals(1, now()->timestamp - $start);
    }
}
