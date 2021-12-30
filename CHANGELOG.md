# Changelog

All Notable changes to `cron` will be documented in this file

## Next - TBD

### Added

- None

### Fixed

- **[BC Break]** `FieldValidator::increment` and `FieldValidator::decrement` accept any `DateTimeInterface` implementing object but will always return a `DateTimeImmutable` object.

### Deprecated

- None

### Removed

- None

## 0.2.0 - 2021-12-30

### Added

- `CronFieldValidator::decrement` to allow a field validator to decrement a `DateTimeInterface` object if it fails validation.

### Fixed

- **[BC Break]** `Scheduler` constructor variable `$timezone` MUST be provided. If you do not want to supply it use the class named constructors instead.
- Internal optimization of `Scheduler::calculateRun`, internally only `DateTimeImmutable` objects are used instead of the `DateTime` class.
- If the `$startDate` value is a string, the `Scheduler` will assume that the value has the same timezone as the underlying system. (Wording fix on the `CronScheduler` interface)

### Deprecated

- None

### Removed

- `CronFieldValidator::increment` no longer has a `$invert` boolean argument. It is dropped and a `CronFieldValidator::decrement` method is introduced instead.

## 0.1.0 - 2021-12-29

Initial Release of `cron`
