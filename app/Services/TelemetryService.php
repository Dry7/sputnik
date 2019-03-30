<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Sputnik\Models\Operations\Operation;

class TelemetryService
{
    private const OPERATIONS = [
        Operation::ORIENTATION_AZIMUTH_ANGLE_DEG,
        Operation::ORIENTATION_ZENITH_ANGLE_DEG,
        Operation::VESSEL_ALTITUDE_M,
        Operation::VESSEL_SPEED_MPS,
        Operation::MAIN_ENGINE_FUEL_PCT,
        Operation::TEMPERATURE_INTERNAL_DEG,
    ];

    public function send()
    {

    }
}