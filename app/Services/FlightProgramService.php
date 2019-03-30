<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Sputnik\Models\FlightProgram;

class FlightProgramService
{
    /**
     * @param string $fileName
     */
    public function load(string $fileName)
    {
        $file = file_get_contents($fileName);

        $data = json_decode($file);

        $flightProgram = FlightProgram::fromJson($data);

        print_r($flightProgram);

//        foreach ($flightProgram->getOperations() as $operation) {
//            echo "\n";
//            print_r((string)$operation);
//        }
        echo "\n";
        print_r($flightProgram->createSchedule());
    }
}