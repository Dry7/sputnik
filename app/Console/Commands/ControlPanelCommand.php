<?php

declare(strict_types=1);

namespace Sputnik\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Sputnik\Services\FlightProgramService;
use Tests\Feature\ControlPanelCommandTest;

class ControlPanelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sputnik:control-panel
                               {--file=}
                               {--test=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sputnik control panel (main process)';

    /**
     * Execute the console command.
     *
     * @param FlightProgramService $flightProgramService
     *
     * @return mixed
     */
    public function handle(FlightProgramService $flightProgramService): void
    {
        $test = $this->option('test');

        if ($test) {
            ControlPanelCommandTest::environment($test);
        }

        Log::info('Let`s go');

        $fileName = $this->option('file')
            ?? config('sputnik.flight_program');

        Log::info('Filename: ' . $fileName);

        $flightProgram = $flightProgramService->load($fileName);

        $flightProgramService->run($flightProgram);
    }
}
