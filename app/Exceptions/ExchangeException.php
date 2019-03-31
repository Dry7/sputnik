<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Exception;
use Throwable;

class ExchangeException extends Exception
{
    private $context;

    public function __construct($message = "", $context = [], $code = 0, Throwable $previous = null)
    {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }

    public static function json($context = [])
    {
        return new static('ExchangeService: invalid json', $context);
    }
}