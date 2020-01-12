<?php

declare(strict_types=1);

namespace Sputnik\Models\Events;

use Illuminate\Support\Facades\Log;
use Sputnik\Exceptions\BaseException;
use Sputnik\Exceptions\EventException;
use Sputnik\Exceptions\InvalidCheck;
use Sputnik\Exceptions\InvalidEvent;
use Sputnik\Helpers\Utils;
use Sputnik\Models\Operations\Operation;
use Closure;

abstract class Event
{
    public const TYPE_START_OPERATION = 'start_operation';
    public const TYPE_CHECK_OPERATION_RESULTS = 'check_operation_results';

    protected const TYPES = [
        self::TYPE_START_OPERATION,
        self::TYPE_CHECK_OPERATION_RESULTS,
    ];

    private int $time;
    private string $type;
    private ?Operation $operation;

    /**
     * Event constructor.
     *
     * @param int $time
     * @param string $type
     * @param Operation|null $operation
     */
    public function __construct(int $time, string $type, Operation $operation = null)
    {
        $this->setTime($time);
        $this->setType($type);
        $this->setOperation($operation);
    }

    abstract public function execute();

    public function validateResult($data): bool
    {
        if (!isset($data->{$this->getOperation()->variable()}->set)
            || !isset($data->{$this->getOperation()->variable()}->value)
        ) {
            $this->throwValueException($data);
        }

        try {
            /** @var Operation $validateOperation */
            $validateOperation = clone $this->getOperation();
            $validateOperation->setValue($data->{$this->getOperation()->variable()}->set)->validate();
            $validateOperation->setValue($data->{$this->getOperation()->variable()}->value)->validate();
        } catch (\TypeError $exception) {
            $this->throwValueException($data);
        }

        return true;
    }

    private function throwValueException($data): void
    {
        switch ($this->type) {
            case self::TYPE_START_OPERATION:
                throw EventException::failedCheck(['type' => $this->type, 'event' => (string)$this, 'data' => Utils::json($data)]);

            default:
                throw InvalidCheck::value(['type' => $this->type, 'event' => (string)$this, 'data' => Utils::json($data)]);
        }
    }

    /**
     * @param int $time
     * @param string $type
     * @param Operation $operation
     *
     * @return Event
     */
    public static function createEvent(int $time, string $type, Operation $operation): self
    {
        switch ($type) {
            case self::TYPE_START_OPERATION:
                return new StartOperationEvent($time, $operation);

            case self::TYPE_CHECK_OPERATION_RESULTS:
                return new CheckOperationResultsEvent($time, $operation);

            default:
                throw InvalidEvent::type([
                    'method' => 'createEvent',
                    'time' => $time,
                    'operation' => (string)$operation,
                ]);
        }
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Operation
     */
    public function getOperation(): Operation
    {
        return $this->operation;
    }

    /**
     * @param int $value
     *
     * @return Event
     */
    public function setTime(int $value): self
    {
        if ($value < 0) {
            throw InvalidEvent::time(['event' => (string)$this, 'value' => $value]);
        }

        $this->time = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Event
     */
    public function setType(string $value): self
    {
        if (!in_array($value, self::TYPES)) {
            throw InvalidEvent::type(['event' => (string)$this, 'value' => $value]);
        }

        $this->type = $value;

        return $this;
    }

    /**
     * @param Operation $value
     *
     * @return Event
     */
    public function setOperation(Operation $value): self
    {
        $this->operation = $value;

        return $this;
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return Utils::json([
            'class' => static::class,
            'type' => $this->type,
            'operation' => (string)$this->operation,
        ]);
    }

    /**
     * @param Closure $callback
     *
     * @return bool|mixed
     *
     * @throws BaseException
     */
    protected function critical(Closure $callback)
    {
        try {
            return $callback();
        } catch (BaseException $exception) {
            if ($this->getOperation()->critical()) {
                throw $exception;
            }
            Log::warning($exception->getMessage(), $exception->getContext());

            return false;
        }
    }
}
