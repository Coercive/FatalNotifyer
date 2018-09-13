<?php
namespace Coercive\Utility\FatalNotifyer;

use Closure;
use Exception;
use ErrorException;
use Coercive\Utility\FatalNotifyer\Exceptions\CompileErrorException;
use Coercive\Utility\FatalNotifyer\Exceptions\CoreErrorException;
use Coercive\Utility\FatalNotifyer\Exceptions\CoreWarningException;
use Coercive\Utility\FatalNotifyer\Exceptions\DeprecatedException;
use Coercive\Utility\FatalNotifyer\Exceptions\NoticeException;
use Coercive\Utility\FatalNotifyer\Exceptions\ParseException;
use Coercive\Utility\FatalNotifyer\Exceptions\RecoverableErrorException;
use Coercive\Utility\FatalNotifyer\Exceptions\StrictException;
use Coercive\Utility\FatalNotifyer\Exceptions\UserDeprecatedException;
use Coercive\Utility\FatalNotifyer\Exceptions\UserErrorException;
use Coercive\Utility\FatalNotifyer\Exceptions\UserNoticeException;
use Coercive\Utility\FatalNotifyer\Exceptions\UserWarningException;
use Coercive\Utility\FatalNotifyer\Exceptions\WarningException;

/**
 * FatalNotifyer
 *
 * @package 	Coercive\Utility\FatalNotifyer
 * @link		https://github.com/Coercive/FatalNotifyer
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class FatalNotifyer
{
	/** @var array Is error in handled list */
	private $handled = [];

	/** @var array Custom handler function list */
	private $custom = [];

	/** @var array Save error in personal log */
	private $log = [];

	/** @var array Dests emails to notify */
	private $notify = [];

	/** @var array Dests emails for full datas */
	private $dests = [];

	/** @var string Emails subject */
	private $subject = 'Coercive\\FatalNotifyer Reporting System';

	/**
	 * DEFINE FATAL ERROR
	 *
	 * @return void
	 */
	private function defineFatalError()
	{
		if(defined('E_FATAL')) { return; }
		define('E_FATAL',  E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);
	}

	/**
	 * ERROR LEVEL TO TEXT
	 *
	 * @param int $severity
	 * @return string
	 */
	private function severityToText(int $severity): string
	{
		switch ($severity) {
			/** Fatal run-time errors */
			case E_ERROR: return "E_ERROR ($severity) : Fatal run-time errors";
			/** Run-time warnings (non-fatal errors) */
			case E_WARNING: return "E_WARNING ($severity) : Run-time warnings (non-fatal errors)";
			/** Compile-time parse errors */
			case E_PARSE: return "E_PARSE ($severity) : Compile-time parse errors";
			/** Run-time notices */
			case E_NOTICE: return "E_NOTICE ($severity) : Run-time notices";
			/** Fatal errors that occur during PHP's initial startup */
			case E_CORE_ERROR: return "E_CORE_ERROR ($severity) : Fatal errors that occur during PHP's initial startup";
			/** Warnings (non-fatal errors) that occur during PHP's initial startup */
			case E_CORE_WARNING: return "E_CORE_WARNING ($severity) : Warnings (non-fatal errors) that occur during PHP's initial startup";
			/** Fatal compile-time errors (Zend) */
			case E_COMPILE_ERROR: return "E_COMPILE_ERROR ($severity) : Fatal compile-time errors (Zend)";
			/** Compile-time warnings (non-fatal errors) */
			case E_COMPILE_WARNING: return "E_COMPILE_WARNING ($severity) : Compile-time warnings (non-fatal errors)";
			/** User-generated error message */
			case E_USER_ERROR: return "E_USER_ERROR ($severity) : User-generated error message";
			/** User-generated warning message */
			case E_USER_WARNING: return "E_USER_WARNING ($severity) : User-generated warning message";
			/** User-generated notice message */
			case E_USER_NOTICE: return "E_USER_NOTICE ($severity) : User-generated notice message";
			/** PHP suggest changes to your code */
			case E_STRICT: return "E_STRICT ($severity) : PHP suggest changes to your code";
			/** Catchable fatal error */
			case E_RECOVERABLE_ERROR: return "E_RECOVERABLE_ERROR ($severity) : Catchable fatal error";
			/** Run-time notices */
			case E_DEPRECATED: return "E_DEPRECATED ($severity) : Run-time notices";
			/** User-generated warning message */
			case E_USER_DEPRECATED: return "E_USER_DEPRECATED ($severity) : User-generated warning message";
			/** Unknown error */
			default: return "Undefined ($severity) : Unknown error";
		}
	}

	/**
	 * THROW ERROR
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @param array $context [optional]
	 * @return void
	 * @throws ErrorException
	 */
	private function throwException(int $severity, string $message, string $fileName, int $line, array $context = [])
	{
		switch ($severity) {
			/** Fatal run-time errors */
			case E_ERROR:
				throw new ErrorException($message, 0, $severity, $fileName, $line);
			/** Run-time warnings (non-fatal errors) */
			case E_WARNING:
				throw new WarningException($message, 0, $severity, $fileName, $line);
			/** Compile-time parse errors */
			case E_PARSE:
				throw new ParseException($message, 0, $severity, $fileName, $line);
			/** Run-time notices */
			case E_NOTICE:
				throw new NoticeException($message, 0, $severity, $fileName, $line);
			/** Fatal errors that occur during PHP's initial startup */
			case E_CORE_ERROR:
				throw new CoreErrorException($message, 0, $severity, $fileName, $line);
			/** Warnings (non-fatal errors) that occur during PHP's initial startup */
			case E_CORE_WARNING:
				throw new CoreWarningException($message, 0, $severity, $fileName, $line);
			/** Fatal compile-time errors (Zend) */
			case E_COMPILE_ERROR:
				throw new CompileErrorException($message, 0, $severity, $fileName, $line);
			/** Compile-time warnings (non-fatal errors) */
			case E_COMPILE_WARNING:
				throw new CoreWarningException($message, 0, $severity, $fileName, $line);
			/** User-generated error message */
			case E_USER_ERROR:
				throw new UserErrorException($message, 0, $severity, $fileName, $line);
			/** User-generated warning message */
			case E_USER_WARNING:
				throw new UserWarningException($message, 0, $severity, $fileName, $line);
			/** User-generated notice message */
			case E_USER_NOTICE:
				throw new UserNoticeException($message, 0, $severity, $fileName, $line);
			/** PHP suggest changes to your code */
			case E_STRICT:
				throw new StrictException($message, 0, $severity, $fileName, $line);
			/** Catchable fatal error */
			case E_RECOVERABLE_ERROR:
				throw new RecoverableErrorException($message, 0, $severity, $fileName, $line);
			/** Run-time notices */
			case E_DEPRECATED:
				throw new DeprecatedException($message, 0, $severity, $fileName, $line);
			/** User-generated warning message */
			case E_USER_DEPRECATED:
				throw new UserDeprecatedException($message, 0, $severity, $fileName, $line);
			/** Unknown error */
			default:
				throw new ErrorException($message, 0, $severity, $fileName, $line);
		}
	}

	/**
	 * HANDLE ERROR
	 *
	 * @param int $severity
	 * @return bool
	 */
	private function isHandledError(int $severity): bool
	{
		foreach ($this->handled as $errorType) {
			if($severity & $errorType) {
				return true;
			}
		}
		return false;
	}

	/**
	 * HANDLE ERROR
	 *
	 * @return void
	 */
	private function handleError()
	{
		# Singleload
		static $single = false;
		if($single) { return; }
		$single = true;

		# Set error handler
		set_error_handler([$this, 'errorHandler']);
	}

	/**
	 * HANDLE FATAL
	 *
	 * @return void
	 */
	private function handleFatal()
	{
		# Singleload
		static $single = false;
		if($single) { return; }
		$single = true;

		# Set fatal shutdown handler
		register_shutdown_function([$this, 'fatalHandler']);
	}

	/**
	 * INIT HANDLER
	 *
	 * @param int $severity
	 * @return void
	 */
	private function initHandler(int $severity)
	{
		# Basic levels
		$this->handleError();

		# Fatal level
		if($severity & E_FATAL) { $this->handleFatal(); }
	}

	/**
	 * STATIC : RESET ERROR REPORTING
	 *
	 * @return void
	 */
	static public function reset()
	{
		# Reset error handler
		restore_error_handler();
		restore_exception_handler();

		# (alternative) Reset error handler
		set_error_handler(null);
		set_exception_handler(null);

		# Default no display and report all
		ini_set('display_errors', 'off');
		error_reporting(E_ALL | E_STRICT);
	}

	/**
	 * AUTO TEST
	 *
	 * @param bool $notice [optional]
	 * @param bool $warning [optional]
	 * @param bool $error [optional]
	 *
	 * @return void
	 */
	static public function autoTest(bool $notice = true, bool $warning = false, bool $error = false)
	{
		# (NOTICE)
		if($notice) {
			echo __METHOD__ . " ----\nNOTICE\n";
			trigger_error(__METHOD__ . ' - NOTICE', E_USER_NOTICE);
		}

		# (WARNING)
		if($warning) {
			echo __METHOD__ . " ----\nWARNING\n";
			trigger_error(__METHOD__ . ' - WARNING', E_USER_WARNING);
		}

		# (ERROR)
		if($error) {
			echo __METHOD__ . " ----\nERROR\n";
			trigger_error(__METHOD__ . ' - ERROR', E_USER_ERROR);
		}
	}

	/**
	 * FatalNotifyer constructor.
	 *
	 * @param int $severity [optional] Report severity
	 */
	public function __construct(int $severity = E_ALL | E_STRICT)
	{
		# const E_FATAL
		$this->defineFatalError();

		# Report errors
		error_reporting($severity);
	}

	/**
	 * DISPLAY ERROR
	 *
	 * PHP init display_errors status
	 *
	 * @param bool $status
	 * @return $this
	 */
	public function displayError(bool $status): FatalNotifyer
	{
		ini_set('display_errors', $status ? 'on' : 'off');
		return $this;
	}

	/**
	 * GET BACKTRACE
	 *
	 * @return string
	 */
	public function getBacktrace(): string
	{
		return (string) print_r(debug_backtrace(false), true);
	}

	/**
	 * MAIN ERROR HANDLER
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @param array|null $context [optional]
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @throws ErrorException
	 */
	public function errorHandler(int $severity, string $message, string $fileName, int $line, $context = []): bool
	{
		# This error code is not included in error_reporting
		# Or error was suppressed with the '@' operator
		if (!(error_reporting() & $severity)) { return false; }

		# Handle type because php suck
		$context = $context ? (array) $context : [];

		# Send notify email if
		foreach($this->notify as $email => $errorType) {
			if($errorType & $severity) {
				(new FatalMailFormater)
					->setSubject($this->subject)
					->setEmails([$email])
					->setNotifyOnly($this->severityToText($severity))
					->send();
			}
		}

		# Send mail if
		foreach($this->dests as $email => $errorType) {
			if($errorType & $severity) {
				(new FatalMailFormater)
					->setSubject($this->subject)
					->setEmails([$email])
					->setError($severity, $message, $fileName, $line, $context, $this->getBacktrace())
					->send();
			}
		}

		# Save if
		foreach($this->log as $path => $errorType) {
			if($errorType & $severity) {
				(new FatalLog($path))
					->save($severity, $message, $fileName, $line, $context, $this->getBacktrace());
			}
		}

		# Launch custom handler : Exec closure
		if(array_key_exists($severity, $this->custom)) {
			$this->custom[$severity]($severity, $message, $fileName, $line, $context, $this->getBacktrace());
		}

		# Throw if registered and no custom handler
		elseif(self::isHandledError($severity) ) {
			$this->throwException($severity, $message, $fileName, $line, $context);
		}

		# Don't execute PHP internal error handler
		return true;
	}

	/**
	 * FATAL ERROR HANDLER
	 *
	 * Redirect to classical errorHandler
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @throws ErrorException
	 */
	public function fatalHandler(): bool
	{
		# Retrieve last error
		$error = error_get_last();
		if(!$error) { return false; }

		# Handle fatal only
		if(isset($error['type']) && ($error['type'] & E_FATAL)) {
			$this->errorHandler($error['type'], $error['message'] ?? '', $error['file'] ?? '', $error['line'] ?? '');
		}

		# Don't execute PHP internal error handler
		return true;
	}

	/**
	 * SET EMAIL SUBJECT
	 *
	 * @param string $subject
	 * @return $this
	 */
	public function setMailSubject(string $subject): FatalNotifyer
	{
		$this->subject = $subject;
		return $this;
	}

	/**
	 * ADD EMAIL
	 *
	 * @param array $emails
	 * @param int $severity [optional]
	 * @return $this
	 */
	public function mail(array $emails, int $severity = E_ALL | E_STRICT): FatalNotifyer
	{
		# Add Process
		foreach ($emails as $email)
		{
			# Skip on error
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)) { continue; }

			# Set email
			$this->dests[$email] = $severity;
		}

		# SET HANDLERS
		$this->initHandler($severity);

		# Maintain chainability
		return $this;
	}

	/**
	 * CUSTOM ERROR HANDLER
	 *
	 * @param int $severity
	 * @param Closure $handler
	 * @return FatalNotifyer
	 */
	public function custom(int $severity, Closure $handler): FatalNotifyer
	{
		# Add severity custom handler
		$this->custom[$severity] = $handler;

		# Maintain chainability
		return $this;
	}

	/**
	 * REGISTER ERROR HANDLER
	 *
	 * @param int $severity [optional]
	 * @return $this
	 */
	public function register(int $severity = E_ALL | E_STRICT): FatalNotifyer
	{
		# SET SPECIFIC HANDLER
		$this->handled[] = $severity;

		# SET HANDLERS
		$this->initHandler($severity);

		# Maintain chainability
		return $this;
	}

	/**
	 * SAVE
	 *
	 * @param string $directory
	 * @param int $severity [optional]
	 * @return $this
	 */
	public function save(string $directory, int $severity = E_ALL | E_STRICT): FatalNotifyer
	{
		# SET SPECIFIC SAVE
		$this->log[$directory] = $severity;

		# SET HANDLERS
		$this->initHandler($severity);

		# Maintain chainability
		return $this;
	}

	/**
	 * NOTIFY EMAIL ERRORS
	 *
	 * @param array $emails
	 * @param int $severity [optional]
	 * @return $this
	 */
	public function notify(array $emails, int $severity = E_ALL | E_STRICT): FatalNotifyer
	{
		# Add Process
		foreach ($emails as $email)
		{
			# Skip on error
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)) { continue; }

			# Set email
			$this->notify[$email] = $severity;
		}

		# SET HANDLERS
		$this->initHandler($severity);

		# Maintain chainability
		return $this;
	}
}
