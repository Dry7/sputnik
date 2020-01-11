<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Carbon\Carbon;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Sputnik\Exceptions\Handler;
use Sputnik\Exceptions\InvalidEvent;
use Sputnik\Services\TerminateService;
use Tests\TestCase;
use Mockery;
use Exception;

class HandlerTest extends TestCase
{
    /** @var Container|Mockery\Mock */
    private $container;

    /** @var Handler */
    private $handler;

    public function setUp(): void
    {
        $this->container = Mockery::mock(Container::class);
        $this->handler= new Handler($this->container);

        Carbon::setTestNow('2013-03-01T16:15:09+03:00');

        parent::setUp();
    }

    public function testReport()
    {
        $logger = Mockery::mock(LogManager::class);
        $terminateService = Mockery::mock(TerminateService::class);

        $this->container->shouldReceive('make')->with(LoggerInterface::class)->andReturn($logger);
        app()->instance(TerminateService::class, $terminateService);

        $exception = InvalidEvent::operation();

        $terminateService->shouldReceive('exit')->with(14)->once();
        $logger->shouldReceive('error')->once();

        $this->handler->report($exception);
    }

    public function testRender()
    {
        // arrange
        /** @var Request $request */
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);
        $exception = new Exception('message', 100);

        // act
        /** @var Response $result */
        $result = $this->handler->render($request, $exception);

        // assert
        self::assertEquals("{\n    \"message\": \"Server Error\"\n}", $result->content());
    }
}
