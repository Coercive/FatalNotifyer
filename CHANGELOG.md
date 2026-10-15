CHANGELOG
=========

0.1.0
-----

 * Compatible PHP 7.4 → 8.5 (requires PHP >= 7.4)
 * `FatalNotifyer` and `FatalLog` are now `final`
 * **Removed the mailer**: `mail()`, `notify()`, `setMailSubject()` and `FatalMailFormater`
 * New `listen(int $severity, callable $callback)`: notify yourself the way you want. As many listeners as you want, called for the errors of their severity bitmask, then the error continues (custom handler, exception...)
 * `custom()` accepts any callable, and a bitmask of severities (the exact severity still wins)
 * The backtrace given to `custom()` and `listen()` callbacks is only built if the callback declares this 6th parameter (it was built for each error)
 * New `getSeverityName()`, and constants `FATAL` and `E_STRICT` (the PHP constant is deprecated since 8.4)
 * Default severity `E_ALL` instead of `E_ALL | E_STRICT` (same value on PHP 7.4, and no more deprecation on 8.4)
 * Handlers are set once per instance instead of once for all (a second instance after `reset()` was ignored)
 * The shutdown handler only handles the fatal errors that never reach the error handler (no more double handling or handling of an error suppressed with `@`)
 * Fix: `E_COMPILE_WARNING` threw `CoreWarningException` instead of `CompileWarningException`
 * Fix: shutdown handler gave `''` as line on a fatal error without line (TypeError)
 * Fix: `getBacktrace()` gave `false` to `debug_backtrace()` (TypeError with strict types)
 * Fix `FatalLog`: the `GET` entry contained `$_SERVER`, json errors gave a TypeError, log files opened for writing when read
 * `FatalLog::getDayList()` is sorted (newest first), `getFileList()` too (and returns `[]` for an unknown day)
 * PHPUnit tests
