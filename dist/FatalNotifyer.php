<?php
declare(strict_types=1);

namespace Coercive\Utility\FatalNotifyer;

use Closure;
use ErrorException;
use Exception;
use ReflectionFunction;
use Coercive\Utility\FatalNotifyer\Exceptions\CompileErrorException;
use Coercive\Utility\FatalNotifyer\Exceptions\CompileWarningException;
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
 * @copyright   2018 - 2026 Anthony Moral
 * @license 	MIT
 */
final class FatalNotifyer
{
	/** @var int Value of E_STRICT (the constant is deprecated since PHP 8.4) */
	const E_STRICT = 2048;

	/** @var int Fatal errors (also defined as the global constant E_FATAL) */
	const FATAL = E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR;

	/** @var int Fatal errors that never reach the error handler: only seen at shutdown */
	const SHUTDOWN = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;

	/** @var string[] Severity => name */
	private const NAMES = [
		E_ERROR => 'E_ERROR',
		E_WARNING => 'E_WARNING',
		E_PARSE => 'E_PARSE',
		E_NOTICE => 'E_NOTICE',
		E_CORE_ERROR => 'E_CORE_ERROR',
		E_CORE_WARNING => 'E_CORE_WARNING',
		E_COMPILE_ERROR => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR => 'E_USER_ERROR',
		E_USER_WARNING => 'E_USER_WARNING',
		E_USER_NOTICE => 'E_USER_NOTICE',
		self::E_STRICT => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED => 'E_DEPRECATED',
		E_USER_DEPRECATED => 'E_USER_DEPRECATED',
	];

	/** @var string[] Severity => exception class */
	private const EXCEPTIONS = [
		E_ERROR => ErrorException::class,
		E_WARNING => WarningException::class,
		E_PARSE => ParseException::class,
		E_NOTICE => NoticeException::class,
		E_CORE_ERROR => CoreErrorException::class,
		E_CORE_WARNING => CoreWarningException::class,
		E_COMPILE_ERROR => CompileErrorException::class,
		E_COMPILE_WARNING => CompileWarningException::class,
		E_USER_ERROR => UserErrorException::class,
		E_USER_WARNING => UserWarningException::class,
		E_USER_NOTICE => UserNoticeException::class,
		self::E_STRICT => StrictException::class,
		E_RECOVERABLE_ERROR => RecoverableErrorException::class,
		E_DEPRECATED => DeprecatedException::class,
		E_USER_DEPRECATED => UserDeprecatedException::class,
	];

	/** @var int[] Severities converted to exceptions */
	private array $handled = [];

	/** @var callable[] Severity => custom handler (replaces the exception) */
	private array $custom = [];

	/** @var array[] Listeners: [severity, callback] */
	private array $listeners = [];

	/** @var int[] Directory => severity of the errors saved in it */
	private array $log = [];

	/** @var bool The error handler is set */
	private bool $errorHandlerSet = false;

	/** @var bool The shutdown handler is set */
	private bool $fatalHandlerSet = false;

	/**
	 * Define the global constant E_FATAL
	 *
	 * @return void
	 */
	private function defineFatalError(): void
	{
		if(!defined('E_FATAL')) {
			define('E_FATAL', self::FATAL);
		}
	}

	/**
	 * The callback receives the backtrace (6th parameter)
	 *
	 * @param callable $callback
	 * @return bool
	 */
	private function wantsBacktrace(callable $callback): bool
	{
		try {
			$reflection = new ReflectionFunction(Closure::fromCallable($callback));
			return $reflection->isVariadic() || $reflection->getNumberOfParameters() >= 6;
		}
		catch (Exception $e) {
			return true;
		}
	}

	/**
	 * The custom handler for this severity: exact match first, then bitmask
	 *
	 * @param int $severity
	 * @return callable|null
	 */
	private function getCustom(int $severity): ? callable
	{
		if(isset($this->custom[$severity])) {
			return $this->custom[$severity];
		}
		foreach ($this->custom as $mask => $handler) {
			if($mask & $severity) {
				return $handler;
			}
		}
		return null;
	}

	/**
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
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @return void
	 * @throws ErrorException
	 */
	private function throwException(int $severity, string $message, string $fileName, int $line): void
	{
		$class = self::EXCEPTIONS[$severity] ?? ErrorException::class;
		throw new $class($message, 0, $severity, $fileName, $line);
	}

	/**
	 * @return void
	 */
	private function handleError(): void
	{
		if($this->errorHandlerSet) {
			return;
		}
		$this->errorHandlerSet = true;
		set_error_handler([$this, 'errorHandler']);
	}

	/**
	 * @return void
	 */
	private function handleFatal(): void
	{
		if($this->fatalHandlerSet) {
			return;
		}
		$this->fatalHandlerSet = true;
		register_shutdown_function([$this, 'fatalHandler']);
	}

	/**
	 * @param int $severity
	 * @return void
	 */
	private function initHandler(int $severity): void
	{
		$this->handleError();
		if($severity & self::FATAL) {
			$this->handleFatal();
		}
	}

	/**
	 * Reset error reporting: PHP handlers, no display, report all
	 *
	 * @return void
	 */
	static public function reset(): void
	{
		restore_error_handler();
		restore_exception_handler();

		# (alternative) Reset error handler
		set_error_handler(null);
		set_exception_handler(null);

		ini_set('display_errors', 'off');
		error_reporting(E_ALL);
	}

	/**
	 * Trigger user errors to test the configuration
	 *
	 * Note: since PHP 8.4, triggering E_USER_ERROR also emits an E_DEPRECATED.
	 *
	 * @param bool $notice [optional]
	 * @param bool $warning [optional]
	 * @param bool $error [optional]
	 * @return void
	 */
	static public function autoTest(bool $notice = true, bool $warning = false, bool $error = false): void
	{
		if($notice) {
			echo __METHOD__ . " ----\nNOTICE\n";
			trigger_error(__METHOD__ . ' - NOTICE', E_USER_NOTICE);
		}
		if($warning) {
			echo __METHOD__ . " ----\nWARNING\n";
			trigger_error(__METHOD__ . ' - WARNING', E_USER_WARNING);
		}
		if($error) {
			echo __METHOD__ . " ----\nERROR\n";
			trigger_error(__METHOD__ . ' - ERROR', E_USER_ERROR);
		}
	}

	/**
	 * Severity name, like 'E_USER_WARNING'
	 *
	 * @param int $severity
	 * @return string
	 */
	static public function getSeverityName(int $severity): string
	{
		return self::NAMES[$severity] ?? "UNKNOWN ($severity)";
	}

	/**
	 * FatalNotifyer constructor.
	 *
	 * @param int $severity [optional] Report severity
	 */
	public function __construct(int $severity = E_ALL)
	{
		$this->defineFatalError();
		error_reporting($severity);
	}

	/**
	 * PHP ini display_errors status
	 *
	 * @param bool $status
	 * @return $this
	 */
	public function displayError(bool $status): self
	{
		ini_set('display_errors', $status ? 'on' : 'off');
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBacktrace(): string
	{
		return print_r(debug_backtrace(0), true);
	}

	/**
	 * Main error handler
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName [optional]
	 * @param int $line [optional]
	 * @param array $context [optional] Not given by PHP 8 anymore
	 * @return bool
	 * @throws ErrorException
	 * @throws Exception
	 */
	public function errorHandler(int $severity, string $message, string $fileName = '', int $line = 0, array $context = []): bool
	{
		# This error code is not included in error_reporting
		# Or error was suppressed with the '@' operator
		if (!(error_reporting() & $severity)) {
			return false;
		}

		# The backtrace is built only once, and only if needed
		$backtrace = null;
		$getBacktrace = function () use (&$backtrace): string {
			return $backtrace ??= $this->getBacktrace();
		};

		# Listeners
		foreach ($this->listeners as [$errorType, $callback]) {
			if($errorType & $severity) {
				$callback($severity, $message, $fileName, $line, $context, $this->wantsBacktrace($callback) ? $getBacktrace() : '');
			}
		}

		# Personal log
		foreach ($this->log as $path => $errorType) {
			if($errorType & $severity) {
				(new FatalLog((string) $path))->save($severity, $message, $fileName, $line, $context, $getBacktrace());
			}
		}

		# Custom handler, or throw if registered
		if($custom = $this->getCustom($severity)) {
			$custom($severity, $message, $fileName, $line, $context, $this->wantsBacktrace($custom) ? $getBacktrace() : '');
		}
		elseif($this->isHandledError($severity)) {
			$this->throwException($severity, $message, $fileName, $line);
		}

		# Don't execute PHP internal error handler
		return true;
	}

	/**
	 * Shutdown handler: redirect the fatal errors (that never reach the error handler) to the errorHandler
	 *
	 * @return bool
	 * @throws ErrorException
	 * @throws Exception
	 */
	public function fatalHandler(): bool
	{
		$error = error_get_last();
		if(!$error || !(($error['type'] ?? 0) & self::SHUTDOWN)) {
			return false;
		}
		return $this->errorHandler((int) $error['type'], (string) ($error['message'] ?? ''), (string) ($error['file'] ?? ''), (int) ($error['line'] ?? 0));
	}

	/**
	 * Call your own handler instead of throwing an exception
	 *
	 * The handler receives: int $severity, string $message, string $fileName, int $line, array $context, string $backtrace
	 * The backtrace is only built if the handler declares this 6th parameter.
	 *
	 * @param int $severity Exact severity, or bitmask (the exact severity wins)
	 * @param callable $handler
	 * @return $this
	 */
	public function custom(int $severity, callable $handler): self
	{
		$this->custom[$severity] = $handler;
		return $this;
	}

	/**
	 * Notify yourself: the callback is called for these errors, then they continue (custom handler, exception...)
	 *
	 * The callback receives: int $severity, string $message, string $fileName, int $line, array $context, string $backtrace
	 * The backtrace is only built if the callback declares this 6th parameter.
	 * Add as many listeners as you want: they are all called, in the order they were added.
	 *
	 * @param int $severity Bitmask, like E_WARNING | E_USER_WARNING
	 * @param callable $callback
	 * @return $this
	 */
	public function listen(int $severity, callable $callback): self
	{
		$this->listeners[] = [$severity, $callback];
		$this->initHandler($severity);
		return $this;
	}

	/**
	 * Convert these errors to exceptions
	 *
	 * @param int $severity [optional]
	 * @return $this
	 */
	public function register(int $severity = E_ALL): self
	{
		$this->handled[] = $severity;
		$this->initHandler($severity);
		return $this;
	}

	/**
	 * Save these errors in a personal log
	 *
	 * @see FatalLog
	 *
	 * @param string $directory
	 * @param int $severity [optional]
	 * @return $this
	 */
	public function save(string $directory, int $severity = E_ALL): self
	{
		$this->log[$directory] = $severity;
		$this->initHandler($severity);
		return $this;
	}
}
