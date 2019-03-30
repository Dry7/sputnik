<?php

declare(strict_types=1);

namespace Sputnik\Models;

use Sputnik\Exceptions\InvalidFlightProgram;
use Sputnik\Helpers\Validation;
use Iterator;
use Sputnik\Models\Events\Event;
use stdClass;
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

    public static function fromJson(stdClass $data): self
    {
        if (!isset($data->startUp) || !is_int($data->startUp)) {
            throw InvalidFlightProgram::startUp(['method' => 'fromJson', 'data' => $data]);
        }

        if (!isset($data->operations) || !is_array($data->operations)) {
            throw InvalidFlightProgram::operations(['method' => 'fromJson', 'data' => $data]);
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

            $this->schedule[] = Event::createEvent($time, Event::TYPE_START_OPERATION, $operation);
            $this->schedule[] = Event::createEvent($checkTime, Event::TYPE_CHECK_OPERATION_RESULTS, $operation);
        }

        return $this->schedule;
    }

    private function setStartUp(int $value): self
    {
        if (!Validation::isUInt32($value)) {
            throw InvalidFlightProgram::startUp(['operation' => $this]);
        }

        $this->startUp = $value;

        return $this;
    }

    private function setOperations(Iterator $operations): self
    {
        $this->operations = $operations;

        return $this;
    }

    private static function createOperations(array $operations): Iterator
    {
        foreach ($operations as $operation) {
            yield Operation::createOperationFromJson($operation);
        }
    }
}