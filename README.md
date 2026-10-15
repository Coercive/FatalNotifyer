Coercive FatalNotifyer Utility
==============================

This class allows you to manage your site's errors: convert them to exceptions, handle them yourself, be notified, or save them in a personal log.

Compatible PHP 7.4 → 8.5

Get
---
```
composer require coercive/fatalnotifyer
```

Usage
-----

```php
use Coercive\Utility\FatalNotifyer\FatalNotifyer;

# Instanciate (and set error_reporting, E_ALL by default)
$notifyer = new FatalNotifyer;

# Display errors or not
$notifyer->displayError(true || false);

# Convert errors to exceptions (Exceptions\WarningException, Exceptions\UserNoticeException...)
$notifyer->register(); // E_ALL
$notifyer->register(E_FATAL);

	// E_ERROR, E_WARNING, E_PARSE, E_NOTICE, E_CORE_ERROR,
	// E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
	// E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT,
	// E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED
	// and E_FATAL (or FatalNotifyer::FATAL)

# Handle yourself some errors instead of throwing an exception
# (exact severity, or bitmask: the exact severity wins)
$notifyer->custom(E_USER_WARNING, function (int $severity, string $message) {
	error_log($message);
});

# Save errors in a personal log (see after to explore it)
$notifyer->save('/my/personal/log/directory/fatal', E_FATAL);
$notifyer->save('/my/personal/log/directory/notice', E_NOTICE);

# You can try an autotest for 3 errors type
FatalNotifyer::autoTest();

# And you have a reset function
FatalNotifyer::reset();
```

Notify yourself
---------------

Add as many listeners as you want, each one for its own severity bitmask. They are called in the order they were added, then the error continues (custom handler, exception...).

The callbacks of `listen()` and `custom()` receive `int $severity, string $message, string $fileName, int $line, array $context, string $backtrace`.
The backtrace is only built if the callback declares this 6th parameter.

```php
# Slack for fatal errors
$notifyer->listen(E_FATAL, function (int $severity, string $message, string $fileName, int $line) {
	$slack->send(FatalNotifyer::getSeverityName($severity) . " : $message in $fileName:$line");
});

# Email with the backtrace for warnings
$notifyer->listen(E_WARNING | E_USER_WARNING, function (int $severity, string $message, string $fileName, int $line, array $context, string $backtrace) {
	mail('webmaster@email.email', 'Warning', "$message\n\n$backtrace");
});
```

Explore error log
-----------------
```php
use Coercive\Utility\FatalNotifyer\FatalLog;

# Load
$log = new FatalLog('/my/personal/log/directory/fatal');

# Days list directories (newest first)
# ['2017-07-22', '2017-07-21', '2017-07-20', ...]
$days = $log->getDayList();

# Files list of a day
# ['15_14_57', '15_14_58', ...]
$files = $log->getFileList('2017-07-22');

# Errors of a file
$errors = $log->getFile('/my/personal/log/directory/fatal/2017-07-22/15_14_57');
```

Tests
-----

PHPUnit 12 requires PHP 8.3+ (the library itself stays compatible with PHP 7.4).

```
composer install
vendor/bin/phpunit
```
