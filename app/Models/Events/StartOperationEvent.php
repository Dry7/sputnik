<?php

declare(strict_types=1);

namespace Sputnik\Models\Events;

use Sputnik\Models\Operations\Operation;

class StartOperationEvent extends Event
{
    public function __construct(int $time, Operation $operation)
    {
        parent::__construct($time, self::TYPE_START_OPERATION, $operation);
    }
}
