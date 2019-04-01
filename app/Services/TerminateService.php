<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Illuminate\Support\Facades\Log;

class TerminateService
{
    /**
     * @param int $code
     */
    public function exit(int $code = 0): void
    {
        Log::info('TerminateService::exit', ['code' => $code]);
        exit($code);
    }
}