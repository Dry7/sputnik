<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

class CoolingSystemPowerPctOperation extends Operation
{
    protected const MIN_VALUE = 0;
    protected const MAX_VALUE = 100;

    public function __construct(int $id, int $deltaT, int $value, int $timeout, bool $critical = true)
    {
        parent::__construct($id, $deltaT, self::COOLING_SYSTEM_POWER_PCT, $value, $timeout, $critical);
    }
}
