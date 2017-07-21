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

# Add your email(s) and error list to send
$oFatal->mail('email_1@email.email');
$oFatal->mail(['email_2@email.email', 'email_3@email.email']);
$oFatal->mail('email_4@email.email', E_COMPILE_ERROR);
$oFatal->mail('email_5@email.email', E_NOTICE | E_USER_NOTICE);
	// ...

# Register type error for internal class handle
$oFatal->register(E_FATAL);

	// E_ERROR, E_WARNING, E_PARSE, E_NOTICE, E_CORE_ERROR,
	// E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
	// E_USER_ERROR:, E_USER_WARNING, E_USER_NOTICE, E_STRICT,
	// E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED

# You can try an autotest for 3 errors type
FatalNotifyer::autoTest();

# And you have a reset function
FatalNotifyer::reset();

```
