<?php

declare(strict_types=1);

namespace Sputnik\Models\Events;

class TelemetryEvent extends Event
{
    public function __construct(int $time)
    {
        parent::__construct($time, self::TYPE_TELEMETRY);
    }
}
