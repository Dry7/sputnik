<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Throwable;

class EventException extends BaseException
{
    public function __construct($message = "", $context = [], $code = self::REQUEST, Throwable $previous = null)
    {
        $this->setContext($context);

        parent::__construct($message, $code, $previous);
    }

    public static function failedCheck($context = [])
    {
        return new static('Event: failed check', $context);
    }
}