<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

class TemperatureInternalDegOperation extends Operation
{
    protected const MIN_VALUE = -50;
    protected const MAX_VALUE = 150;

    public function __construct(int $id, int $deltaT, int $value, int $timeout, bool $critical = true)
    {
        parent::__construct($id, $deltaT, self::TEMPERATURE_INTERNAL_DEG, $value, $timeout, $critical);
    }
}