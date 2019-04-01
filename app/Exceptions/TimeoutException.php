<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Throwable;

class TimeoutException extends BaseException
{
    public function __construct($message = "", $context = [], $code = BaseException::TIMEOUT, Throwable $previous = null)
    {
        $this->setContext($context);

        parent::__construct($message, $code, $previous);
    }

    public static function timeout($context = [])
    {
        return new static('Request timeout', $context);
    }
}