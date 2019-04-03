<?php

declare(strict_types=1);

namespace Sputnik\Services;

use Illuminate\Log\LogManager;
use Sputnik\Models\Operations\Operation;

class TelemetryService
{
    public const OPERATIONS = [
        Operation::ORIENTATION_AZIMUTH_ANGLE_DEG,
        Operation::ORIENTATION_ZENITH_ANGLE_DEG,
        Operation::VESSEL_ALTITUDE_M,
        Operation::VESSEL_SPEED_MPS,
        Operation::MAIN_ENGINE_FUEL_PCT,
        Operation::TEMPERATURE_INTERNAL_DEG,
    ];
    private const CHANNEL = 'telemetry';

    /** @var LogManager */
    private $logger;

    public function __construct(LogManager $logger)
    {
        $this->logger = $logger;
    }

    public function send(array $variables)
    {
        $this->logger->info("Telemetry::send", $variables);

        $this->logger->channel(self::CHANNEL)->info($this->createMessage($variables));
    }

    private function createMessage(array $variables)
    {
        return http_build_query(collect(self::OPERATIONS)->mapWithKeys(function ($item) use ($variables) {
            return [$item => $variables[$item]];
        })->toArray());
    }
}