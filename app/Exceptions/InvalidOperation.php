<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Exception;
use Throwable;

class InvalidOperation extends Exception
{
    private $context;

    public function __construct($message = "", $context = [], $code = 0, Throwable $previous = null)
    {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }

    public static function propertyNotFound($context = [])
    {
        return new static('Invalid operation: id', $context);
    }

    public static function id($context = [])
    {
        return new static('Invalid operation: id', $context);
    }

    public static function deltaT($context = [])
    {
        return new static('Invalid operation: deltaT', $context);
    }

    public static function variable($context = [])
    {
        return new static('Invalid operation: variable', $context);
    }

    public static function value($context = [])
    {
        return new static('Invalid operation: value', $context);
    }

    public static function timeout($context = [])
    {
        return new static('Invalid operation: timeout', $context);
    }

    public static function critical($context = [])
    {
        return new static('Invalid operation: critical', $context);
    }
}