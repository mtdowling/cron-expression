<?php

namespace Cron;

use OutOfRangeException;

final class RangeError extends OutOfRangeException implements ExpressionError
{
    public static function dueToInvalidInput(string $type): self
    {
        return new self('Invalid range '.$type.' requested');
    }

    public static function dueToInvalidStep(): self
    {
        return new self('Step cannot be greater than total range');
    }
}
