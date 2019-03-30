<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

class VesselSpeedMpsOperation extends Operation
{
    protected const MIN_VALUE = 0;
    protected const MAX_VALUE = 15000;

    public function __construct(int $id, int $deltaT, int $value, int $timeout, bool $critical = true)
    {
        parent::__construct($id, $deltaT, self::VESSEL_SPEED_MPS, $value, $timeout, $critical);
    }
}