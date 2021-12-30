# Changelog

All Notable changes to `cron` will be documented in this file

## Next - TBD

### Changes

- `Scheduler` constructor variable `$timezone` MUST be provided. If you do not want to supply it use the class named constructors instead.
- Internal changes in run resolution, internally only `DateTimeImmutable` objects are used instead of the `DateTime` class.
- split the `CronFieldValidator::increment` method into two methods to remove boolean arguments from the public API. 
- If the `$startDate` value is a string, the `Scheduler` will assume that the value has the same timezone as the underlying system.

## 0.1.0 - 2021-12-28

Initial Release of `cron`
