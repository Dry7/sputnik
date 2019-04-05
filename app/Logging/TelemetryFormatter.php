<?php

declare(strict_types=1);

namespace Sputnik\Logging;

use Monolog\Formatter\FormatterInterface;
use Sputnik\Helpers\Utils;

class TelemetryFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        return Utils::json([
                'type' => 'values',
                'timestamp' => now()->timestamp,
                'message' => $record['message'],
            ]) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

}