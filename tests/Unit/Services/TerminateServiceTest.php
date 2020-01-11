<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Log\LogManager;
use Mockery;
use Sputnik\Services\TerminateService;
use Tests\TestCase;

class TerminateServiceTest extends TestCase
{
    public function testTesting(): void
    {
        // arrange
        $exitCode = 11;
        $logging = Mockery::mock(LogManager::class);
        /** @var TerminateService $service */
        $service = new TerminateService($logging, false);

        // assert
        $logging
            ->shouldReceive('info')
            ->with('TerminateService::exit', ['code' => $exitCode])
            ->once();

        // act
        $service->exit($exitCode);
    }
}
