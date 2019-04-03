<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Sputnik\Exceptions\InvalidFlightProgram;
use Sputnik\Models\Events\Event;
use Sputnik\Models\FlightProgram;

class FlightProgramService
{
    /** @var TelemetryService */
    private $telemetryService;

    /** @var ExchangeService */
    private $exchangeService;

    /** @var int */
    private $telemetryFreq;

    /** @var TimeService */
    private $timeService;

    /** @var array */
    private $variables;

    public function __construct(
        TelemetryService $telemetryService,
        ExchangeService $exchangeService,
        TimeService $timeService,
        int $telemetryFreq
    )
    {
        $this->telemetryService = $telemetryService;
        $this->exchangeService = $exchangeService;
        $this->timeService = $timeService;
        $this->telemetryFreq = $telemetryFreq;
    }

    /**
     * @param string $fileName
     *
     * @return FlightProgram
     */
    public function load(string $fileName)
    {
        if (!file_exists($fileName)) {
            throw InvalidFlightProgram::fileNotFound(['fileName' => $fileName]);
        } elseif (!is_file($fileName)) {
            throw InvalidFlightProgram::notFile(['fileName' => $fileName]);
        } elseif (!is_readable($fileName)) {
            throw InvalidFlightProgram::permissionDenied(['fileName' => $fileName]);
        }

        $flightProgram = FlightProgram::fromJson(file_get_contents($fileName));

        return $flightProgram;
    }

    public function run(FlightProgram $flightProgram)
    {
        $schedule = $flightProgram->createSchedule();

        $startTime = now()->timestamp;

        $maxTime = $this->calculateEndTime($schedule, $startTime);

        $time = now()->timestamp;

        Log::info("Start time: " . $startTime);
        Log::info("End time: " . $maxTime);

        do {
            $isTelemetry = ($time - $startTime)%$this->telemetryFreq === 0;

            Log::info('Current time: ' . $time);

            $this->executeChecks(
                collect($schedule[$time][Event::TYPE_CHECK_OPERATION_RESULTS] ?? []),
                $isTelemetry
            );
            $this->executeStarts(
                collect($schedule[$time][Event::TYPE_START_OPERATION] ?? [])
            );
            if ($isTelemetry) {
                $this->telemetryService->send($this->variables);
            }
            $this->timeService->sleep(1);
            $time = now()->timestamp;
        } while ($time <= $maxTime);
    }

    private function executeStarts(Collection $events)
    {
        if ($events->isEmpty()) {
            return;
        }

        Log::info('Execute Starts: ', [
            'events' => $events
                ->map(function (Event $event) { return $event->getOperation()->getID(); })
                ->implode(', '),
            ]
        );

        if ($events->count() === 1) {
            $events[0]->execute();
            return;
        }

        // Reduce the number of requests
        $variables = $events
            ->mapWithKeys(function (Event $event) {
                return [$event->getOperation()->variable() => $event->getOperation()->value()];
            })
            ->toArray();

        if (sizeof($variables) !== $events->count()) {
            $events->each(function (Event $event) {
                $event->execute();
            });
            return;
        }

        $data = $this->exchangeService->patch($variables);

        $events->each(function (Event $event) use ($data) {
            $event->validateResult($data);
        });
    }

    private function executeChecks(Collection $events, bool $isTelemetry = false)
    {
        if ($events->isEmpty() && !$isTelemetry) {
            return;
        }

        Log::info('Execute checks: ', [
                'events' => $events
                    ->map(function (Event $event) { return $event->getOperation()->getID(); })
                    ->implode(', '),
            ]
        );

        if ($events->count() === 1 && !$isTelemetry) {
            $events[0]->execute();
            return;
        }

        // Reduce the number of requests
        $variables = $events
            ->map(function (Event $event) { return $event->getOperation()->variable(); })
            ->merge(TelemetryService::OPERATIONS)
            ->unique()
            ->toArray();

        $data = $this->exchangeService->get($variables);

        $events->each(function (Event $event) use ($data) {
            $event->validateResult($data);
        });

        $this->updateCurrentVariables($data);
    }

    private function updateCurrentVariables($data)
    {
        foreach ($data as $key => $value) {
            $this->variables[$key] = $value->value;
        }
    }

    private function calculateEndTime(array &$schedule, int $startTime): int
    {
        if (empty($schedule)) {
            return $startTime;
        }

        return max(array_keys($schedule)) ?? $startTime;
    }
}