<?php

declare(strict_types=1);

namespace Sputnik\Helpers;

class Validation
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public static function isUInt32(int $value)
    {
        return $value >= 0 && $value <= 4294967295;
    }
}