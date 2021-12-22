<?php

namespace Bakame\Cron;

use OutOfRangeException;

final class RangeError extends OutOfRangeException implements ExpressionError
{
    public static function dueToInvalidInput(string $type): self
    {
        return new self('Invalid range '.$type.' requested');
    }
}
