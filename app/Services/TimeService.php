<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Illuminate\Support\Facades\Date;

class TimeService
{
    /** @var bool */
    private $testing;

    public function __construct(bool $testing = false)
    {
        $this->testing = $testing;
    }

    /**
     * @param int $seconds
     */
    public function sleep(int $seconds = 1): void
    {
        if ($this->testing) {
            Date::setTestNow(now()->addSeconds($seconds));
        } else {
            sleep($seconds);
        }
    }
}