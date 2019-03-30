<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Exception;
use Throwable;

class InvalidFlightProgram extends Exception
{
    private $context;

    function __construct($message = "", $context = [], $code = 0, Throwable $previous = null)
    {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }

    public static function startUp($context = [])
    {
        return new static('Invalid flight program: startUp', $context);
    }

    public static function operations($context = [])
    {
        return new static('Invalid flight program: operations', $context);
    }
}