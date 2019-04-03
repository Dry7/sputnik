<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Carbon\Carbon;

class TimeService
{
    /** @var bool */
    private $testing;

    public function __construct(bool $testing)
    {
        $this->testing = $testing;
    }

    /**
     * @param int $seconds
     */
    public function sleep(int $seconds): void
    {
        if ($this->testing) {
            Carbon::setTestNow(now()->addSeconds($seconds));
        } else {
            sleep($seconds);
        }
    }
}