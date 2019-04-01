<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Throwable;

class RequestException extends BaseException
{
    public function __construct($message = "", $context = [], $code = self::REQUEST, Throwable $previous = null)
    {
        $this->setContext($context);

        parent::__construct($message, $code, $previous);
    }

    public static function timeout($context = [])
    {
        return new static('Request timeout', $context);
    }

    public static function json($context = [])
    {
        return new static('ExchangeService: invalid json', $context);
    }
}