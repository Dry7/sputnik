<?php

declare(strict_types=1);

namespace Sputnik\Logging;

use Carbon\Carbon;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use Sputnik\Exceptions\BaseException;
use Sputnik\Helpers\Utils;

class LogFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        if ($record['level'] > Logger::INFO) {
            return '';
        }

        $exception = $record['context']['exception'] ?? null;
        $context = $exception instanceof BaseException
            ? $exception->getContext()
            : $record['context'];

        return Utils::json([
                'time' => now()->toIso8601ZuluString(),
                'type' => 'info',
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