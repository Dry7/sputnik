<?php

declare(strict_types=1);

namespace Sputnik\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Sputnik\Services\FlightProgramService;

class ControlPanelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sputnik:control-panel';

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
    public function handle(FlightProgramService $flightProgramService)
    {
        $flightProgram = $flightProgramService->load(storage_path('flight_program.json'));

        $flightProgramService->run($flightProgram);
    }
}
