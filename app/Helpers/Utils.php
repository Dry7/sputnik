<?php

declare(strict_types=1);

namespace Sputnik\Helpers;

class Utils
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

    public static function json($data)
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}