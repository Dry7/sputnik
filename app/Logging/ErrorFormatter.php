<?php

declare(strict_types=1);

namespace Sputnik\Logging;

use Monolog\Formatter\FormatterInterface;
use Sputnik\Exceptions\BaseException;

class ErrorFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $exception = $record['context']['exception'] ?? null;
        $context = $exception instanceof BaseException
            ? $exception->getContext()
            : $record['context'];

        return "\n" . json_encode([
            'type' => 'error',
            'timestamp' => $record['datetime']->getTimestamp(),
            'message' => $record['message'] . ' ' . print_r($context, true),
        ]);
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