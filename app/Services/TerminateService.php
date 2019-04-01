<?php

declare(strict_types=1);

namespace Sputnik\Services;

class TerminateService
{
    /**
     * @param int $code
     */
    public function exit(int $code = 0): void
    {
        exit($code);
    }
}