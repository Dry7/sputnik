<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

class VesselAltitudeMOperation extends Operation
{
    protected const MIN_VALUE = 0;
    protected const MAX_VALUE = 35000000;

    public function __construct(int $id, int $deltaT, int $value, int $timeout, bool $critical = true)
    {
        parent::__construct($id, $deltaT, self::VESSEL_ALTITUDE_M, $value, $timeout, $critical);
    }
}
