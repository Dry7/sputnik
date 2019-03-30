<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Exception;
use Throwable;

class InvalidEvent extends Exception
{
    private $context;

    function __construct($message = "", $context = [], $code = 0, Throwable $previous = null)
    {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }

    public static function time($context = [])
    {
        return new static('Invalid event: time', $context);
    }

    public static function type($context = [])
    {
        return new static('Invalid event: type', $context);
    }

    public static function operation($context = [])
    {
        return new static('Invalid event: operation', $context);
    }
}