<?php

declare(strict_types=1);

namespace Sputnik\Models\Events;

use Sputnik\Exceptions\EventException;
use Sputnik\Models\Operations\Operation;
use Sputnik\Services\ExchangeService;

class StartOperationEvent extends Event
{
    public function __construct(int $time, Operation $operation)
    {
        parent::__construct($time, self::TYPE_START_OPERATION, $operation);
    }

    public function execute(): bool
    {
        /** @var ExchangeService $service */
        $service = app(ExchangeService::class);

        return $this->validateResult(
            $service->patch(
                [$this->getOperation()->variable() => $this->getOperation()->value()]
            )
        );
    }

    public function validateResult($data): bool
    {
        return $this->critical(function () use ($data) {
            parent::validateResult($data);

            if ($data->{$this->getOperation()->variable()}->set !== $this->getOperation()->value()) {
                throw EventException::failedCheck(['event' => $this, 'data' => json_encode($data)]);
            }

            return true;
        });
    }
}
