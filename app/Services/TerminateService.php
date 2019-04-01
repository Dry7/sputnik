<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Illuminate\Support\Facades\Log;

class TerminateService
{
    /** @var bool */
    private $active;

    public function __construct(bool $active = true)
    {
        $this->active = $active;
    }

    /**
     * @param int $code
     */
    public function exit(int $code = 0): void
    {
        Log::info('TerminateService::exit', ['code' => $code]);

        if ($this->active) {
            exit($code);
        }
    }
}