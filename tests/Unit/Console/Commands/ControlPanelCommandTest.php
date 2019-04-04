<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Command;

use Sputnik\Console\Commands\ControlPanelCommand;
use Sputnik\Models\FlightProgram;
use Sputnik\Services\FlightProgramService;
use Tests\TestCase;

class ControlPanelCommandTest extends TestCase
{
    public function testHandle()
    {
        // arrange
        $fileName = config('sputnik.flight_program');
        $json = $this->readFixture('flight_program/empty.json');
        $this->mock(FlightProgramService::class, function ($mock) use ($json, $fileName) {
            $mock->shouldReceive('load')->with()->with($fileName)->andReturn(FlightProgram::fromJson($json))->once()->getMock()
                 ->shouldReceive('run')->once();
        });

        // act
        $this
            ->artisan(ControlPanelCommand::class)
            ->assertExitCode(0)
            ->run();
    }

    public function testHandleWithFileName()
    {
        // arrange
        $fileName = 'tests/data/flight_program/empty.json';
        $json = $this->readFixture('flight_program/empty.json');
        $this->mock(FlightProgramService::class, function ($mock) use ($json, $fileName) {
            $mock
                ->shouldReceive('load')->with()->with($fileName)->andReturn(FlightProgram::fromJson($json))->once()->getMock()
                ->shouldReceive('run')->once();
        });

        // act
        $this
            ->artisan(ControlPanelCommand::class, ['--file' => $fileName])
            ->assertExitCode(0)
            ->run();
    }

    public function testHandleWithFileNameAndTest()
    {
        // arrange
        $fileName = 'tests/data/flight_program/empty.json';
        $json = $this->readFixture('flight_program/empty.json');
        $this->mock(FlightProgramService::class, function ($mock) use ($json, $fileName) {
            $mock
                ->shouldReceive('load')->with()->with($fileName)->andReturn(FlightProgram::fromJson($json))->once()->getMock()
                ->shouldReceive('run')->once();
        });

        // act
        $this
            ->artisan(ControlPanelCommand::class, ['--file' => $fileName, '--test' => 'empty_operations'])
            ->assertExitCode(0)
            ->run();
    }
}
