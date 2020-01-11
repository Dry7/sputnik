<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Command;

use Sputnik\Console\Commands\ControlPanelCommand;
use Sputnik\Models\FlightProgram;
use Sputnik\Services\FlightProgramService;
use Tests\TestCase;

class ControlPanelCommandTest extends TestCase
{
    public function testHandle(): void
    {
        // arrange
        $fileName = config('sputnik.flight_program');
        $json = $this->readFixture('flight_program/empty.json');
        $this->mock(FlightProgramService::class, static function ($mock) use ($json, $fileName): void {
            $mock->shouldReceive('load')->with()->with($fileName)->andReturn(FlightProgram::fromJson($json))->once()->getMock()
                 ->shouldReceive('run')->once();
        });

        // act
        $this
            ->artisan(ControlPanelCommand::class)
            ->assertExitCode(0)
            ->run();
    }

    public function testHandleWithFileName(): void
    {
        // arrange
        $fileName = 'tests/data/flight_program/empty.json';
        $json = $this->readFixture('flight_program/empty.json');
        $this->mock(FlightProgramService::class, static function ($mock) use ($json, $fileName): void {
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

    public function testHandleWithFileNameAndTest(): void
    {
        // arrange
        $fileName = 'tests/data/flight_program/empty.json';
        $json = $this->readFixture('flight_program/empty.json');
        $this->mock(FlightProgramService::class, static function ($mock) use ($json, $fileName): void {
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
