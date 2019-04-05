<?php

declare(strict_types=1);

namespace Sputnik\Logging;

use Monolog\Formatter\FormatterInterface;
use Sputnik\Exceptions\BaseException;
use Sputnik\Helpers\Utils;

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

        return Utils::json([
            'type' => 'error',
            'timestamp' => now()->timestamp,
            'message' => $record['message'] . (!empty($context) ? ' ' . Utils::json($context) : ''),
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