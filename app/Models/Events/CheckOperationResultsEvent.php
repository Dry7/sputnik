<?php

declare(strict_types=1);

namespace Sputnik\Models\Events;

use Sputnik\Exceptions\EventException;
use Sputnik\Models\Operations\Operation;
use Sputnik\Services\ExchangeService;

class CheckOperationResultsEvent extends Event
{
    public function __construct(int $time, Operation $operation)
    {
        parent::__construct($time, self::TYPE_CHECK_OPERATION_RESULTS, $operation);
    }

    public function execute()
    {
        /** @var ExchangeService $service */
        $service = app(ExchangeService::class);

        return $this->validateResult(
            $service->get(
                [$this->getOperation()->variable()]
            )
        );
    }

    public function validateResult($data): bool
    {
        return $this->critical(function () use ($data) {
            parent::validateResult($data);

            if ($data->{$this->getOperation()->variable()}->value !== $this->getOperation()->value()) {
                throw EventException::failedCheck(['event' => $this, 'data' => json_encode($data)]);
            }

            return true;
        });
    }
}
