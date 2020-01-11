<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

class OrientationAzimuthAngleDegOperation extends Operation
{
    protected const MIN_VALUE = 0;
    protected const MAX_VALUE = 359;

    public function __construct(int $id, int $deltaT, int $value, int $timeout, bool $critical = true)
    {
        parent::__construct($id, $deltaT, self::ORIENTATION_AZIMUTH_ANGLE_DEG, $value, $timeout, $critical);
    }
}
