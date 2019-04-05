<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Throwable;

class InvalidCheck extends BaseException
{
    function __construct($message = "", $context = [], $code = self::INVALID_EXCHANGE_RESPONSE, Throwable $previous = null)
    {
        $this->setContext($context);

        parent::__construct($message, $code, $previous);
    }

    public static function value($context = [])
    {
        return new static('Invalid check: value', $context);
    }

    public static function exchangeRequest($context = [])
    {
        return new static('Invalid exchangeService', $context);
    }
}