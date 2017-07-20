Coercive FatalNotifyer Utility
==============================

IN WORK

Get
---
```
composer require coercive/fatalnotifyer
```

Usage
-----

```php

use Coercive\Utility\FatalNotifyer\FatalNotifyer;

# Instanciate
$oFatal = new FatalNotifyer;

# Display errors or not
$oFatal->displayError(true || false);

# Set your own email subject, with your project name for example
$oFatal->setMailSubject('Hello, an error occured on my website');

# Add your email(s)
$oFatal->addMailDest('my-first-email@email.email');
$oFatal->addMailDest('my-second-email@email.email');
$oFatal->addMailDest('my-third-email@email.email');
	// ...

# Register type error for handle
$oFatal->registerError(E_FATAL, true, true);

	// first param : error type
	// E_ERROR, E_WARNING, E_PARSE, E_NOTICE, E_CORE_ERROR,
	// E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
	// E_USER_ERROR:, E_USER_WARNING, E_USER_NOTICE, E_STRICT,
	// E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED
	
	// second param is to enable FatalNotifyer handler system
	// third param is to enable email send support for registered errors

# You can try an autotest for 3 errors type
FatalNotifyer::autoTest();

# And you have a reset function
FatalNotifyer::reset();

```
