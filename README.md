Coercive FatalNotifyer Utility
==============================

This class allows you to manage your site's errors with an internal system of exception and notification by email.

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

# Or just notify (not the full backtrace but short message)
$oFatal->notify('email_1@email.email');
$oFatal->notify(['email_2@email.email', 'email_3@email.email'], E_NOTICE | E_USER_NOTICE);

# Register type error for internal class handle
$oFatal->register(E_FATAL);

	// E_ERROR, E_WARNING, E_PARSE, E_NOTICE, E_CORE_ERROR,
	// E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
	// E_USER_ERROR:, E_USER_WARNING, E_USER_NOTICE, E_STRICT,
	// E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED

# Save type error in personal file
$oFatal->save('/my/personal/log/directory/fatal', E_FATAL);
$oFatal->save('/my/personal/log/directory/notice', E_NOTICE);
// See after for explore errors datas


# You can try an autotest for 3 errors type
FatalNotifyer::autoTest();

# And you have a reset function
FatalNotifyer::reset();

```

Explore error log
-----------------
```php
use Coercive\Utility\FatalNotifyer\FatalLog;

# For example, you can imagine an ajax system that retrieve error list ...

# Load
$oLog = new FatalLog('/my/personal/log/directory/fatal');

# Retreive days list directories
# ['2017-07-22', '2017-07-21', '2017-07-20', ...]

$aDays = $oLog->getDayList();

# Retreive file list by directories
# ['2017-07-22' => ['15_14_57', '15_14_58', ...], ...]

$aLists = [];
foreach ($aDays as $sDay) {
	$aLists[$sDay] = $oLog->getFileList($sDay);
}

# Retrieve one (example)

$oFatalBazooka->getOne("/my/personal/log/directory/fatal/2017-07-22/15_14_57");

# Loop retrieve (example)

$aErrors = [];
foreach ($aLists as $sDay => $aFiles) {
	foreach ($aFiles as $sFile) {
		$aErrors[$sDay . '@' . $sFile] = $oFatalBazooka->getOne("/my/personal/log/directory/fatal/$sDay/$sFile");
	}
}

# See results

echo '<pre>';
var_dump($aDays);
var_dump($aLists);
var_dump($aErrors);
echo '</pre>';

```
