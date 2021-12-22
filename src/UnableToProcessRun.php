<?php

namespace Cron;

use RuntimeException;

final class UnableToProcessRun extends RuntimeException implements ExpressionError
{
    public static function dueToMaxIterationCountReached(int $iterationCount): self
    {
        return new self('Unable to perform the process as the max iteration count `'.$iterationCount.'` has been reached.');
    }
}
