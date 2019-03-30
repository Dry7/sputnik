<?php

declare(strict_types=1);

namespace Sputnik\Models\Events;

use Sputnik\Models\Operations\Operation;

class CheckOperationResultsEvent extends Event
{
    public function __construct(int $time, Operation $operation)
    {
        parent::__construct($time, self::TYPE_CHECK_OPERATION_RESULTS, $operation);
    }
}
