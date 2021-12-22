<?php

namespace Bakame\Cron;

use DateTimeZone;

interface TimezonedExpression extends Expression
{
    /**
     * Returns the timezone attached to the CRON expression.
     */
    public function timezone(): DateTimeZone;

    /**
     * Sets the timezone attached to the CRON expression.
     */
    public function withTimezone(DateTimeZone|string $timezone): self;
}
