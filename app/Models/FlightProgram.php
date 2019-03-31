<?php

declare(strict_types=1);

namespace Sputnik\Models;

use Sputnik\Exceptions\InvalidFlightProgram;
use Sputnik\Helpers\Validation;
use Iterator;
use Sputnik\Models\Events\Event;
use Sputnik\Models\Operations\Operation;

class FlightProgram
{
    /** @var int */
    private $startUp;

    /** @var Operation[]|Iterator */
    private $operations;

    /** @var array */
    private $schedule;

    public function __construct(int $startUp, Iterator $operations)
    {
        $this->setStartUp($startUp);
        $this->setOperations($operations);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidFlightProgram::json([
                'method' => 'fromJson',
                'error' => json_last_error_msg(),
                'json' => $json
            ]);
        }

        if (!isset($data->startUp) || !is_int($data->startUp)) {
            throw InvalidFlightProgram::startUp(['method' => 'fromJson', 'data' => $data, 'json' => $json]);
        }

        if (!isset($data->operations) || !is_array($data->operations)) {
            throw InvalidFlightProgram::operations(['method' => 'fromJson', 'data' => $data, 'json' => $json]);
        }

        return new static($data->startUp, self::createOperations($data->operations));
    }

    public function getStartUp(): int
    {
        return $this->startUp;
    }

    public function getOperations(): Iterator
    {
        return $this->operations;
    }

    public function getSchedule(): array
    {
        return $this->schedule;
    }

    public function createSchedule(): array
    {
        $this->schedule = [];

        foreach ($this->operations as $operation) {
            $time = $operation->time($this->startUp);
            $checkTime = $time + $operation->timeout();

            $this->pushToSchedule(Event::createEvent($time, Event::TYPE_START_OPERATION, $operation));
            $this->pushToSchedule(Event::createEvent($checkTime, Event::TYPE_CHECK_OPERATION_RESULTS, $operation));
        }

        return $this->schedule;
    }

    public function setStartUp(int $value): self
    {
        if (!Validation::isUInt32($value)) {
            throw InvalidFlightProgram::startUp(['operation' => $this, 'value' => $value]);
        }

        $this->startUp = $value;
//        $this->startUp = now()->timestamp + 10;

        return $this;
    }

    public function setOperations(Iterator $operations): self
    {
        $this->operations = $operations;

        return $this;
    }

    private function pushToSchedule(Event $event): void
    {
        if (isset($this->schedule[$event->getTime()])) {
            if (isset($this->schedule[$event->getTime()][$event->getType()])) {
                $this->schedule[$event->getTime()][$event->getType()][] = $event;
            } else {
                $this->schedule[$event->getTime()][$event->getType()] = [$event];
            }
        } else {
            $this->schedule[$event->getTime()] = [$event->getType() => [$event]];
        }
    }

    private static function createOperations(array $operations): Iterator
    {
        foreach ($operations as $operation) {
            yield Operation::createOperationFromJsonObject($operation);
        }
    }
}