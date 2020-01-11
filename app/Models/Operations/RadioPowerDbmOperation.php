<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

class RadioPowerDbmOperation extends Operation
{
    protected const MIN_VALUE = 20;
    protected const MAX_VALUE = 80;

    public function __construct(int $id, int $deltaT, int $value, int $timeout, bool $critical = true)
    {
        parent::__construct($id, $deltaT, self::RADIO_POWER_DBM, $value, $timeout, $critical);
    }
}
