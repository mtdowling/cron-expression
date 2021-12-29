# Changelog

All Notable changes to `cron` will be documented in this file

## Next - TBD

### Changes

- `Scheduler` constructor variable `$timezone` MUST be provided. If you do not want to use the class named constructors instead.
- Internal changes we now uses by default `DateTimeImmutable` instead of the `DateTime` class.
- split the `CronFieldValidator::increment` method into two method ease maintenance. 

## 0.1.0 - 2021-12-28

Initial Release of `cron`
