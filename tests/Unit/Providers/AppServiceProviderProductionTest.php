<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use GuzzleHttp\HandlerStack;
use Tests\TestCase;

class AppServiceProviderProductionTest extends TestCase
{
    public function setUp(): void
    {
        putenv('APP_ENV=production');
        parent::setUp();
    }

    public function tearDown(): void
    {
        putenv('APP_ENV=testing');
        parent::tearDown();
    }

    public function testHandlerStackInProdMode(): void
    {
        // act
        $instance = app(HandlerStack::class);

        // assert
        $this->assertInstanceOf(HandlerStack::class, $instance);
    }
}
