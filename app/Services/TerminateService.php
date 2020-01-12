<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Illuminate\Log\LogManager;

class TerminateService
{
    private bool $active;
    private LogManager $logger;

    public function __construct(LogManager $logger, bool $active = true)
    {
        $this->logger = $logger;
        $this->active = $active;
    }

    /**
     * @param int $code
     */
    public function exit(int $code = 0): void
    {
        $this->logger->info('TerminateService::exit', ['code' => $code]);

        // @codeCoverageIgnoreStart
        if ($this->active) {
            exit($code);
        }
        // @codeCoverageIgnoreEnd
    }
}
