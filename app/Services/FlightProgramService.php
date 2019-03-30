<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Sputnik\Exceptions\InvalidFlightProgram;
use Sputnik\Models\FlightProgram;

class FlightProgramService
{
    /**
     * @param string $fileName
     */
    public function load(string $fileName)
    {
        if (!file_exists($fileName)) {
            throw InvalidFlightProgram::fileNotFound(['fileName' => $fileName]);
        } elseif (!is_file($fileName)) {
            throw InvalidFlightProgram::notFile(['fileName' => $fileName]);
        } elseif (!is_readable($fileName)) {
            throw InvalidFlightProgram::permissionDenied(['fileName' => $fileName]);
        }

        $file = file_get_contents($fileName);

        $flightProgram = FlightProgram::fromJson($file);

        print_r($flightProgram);

//        foreach ($flightProgram->getOperations() as $operation) {
//            echo "\n";
//            print_r((string)$operation);
//        }
        echo "\n";
        print_r($flightProgram->createSchedule());
    }
}