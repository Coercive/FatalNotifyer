<?php
namespace Coercive\Utility\FatalNotifyer;

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
 * PHP Version 7.1
 *
 * @version		1
 * @package 	Coercive\Utility\FatalNotifyer
 * @link		https://github.com/Coercive/FatalNotifyer
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2017 - 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class FatalNotifyer {

	/** @var array Handlers */
	private $_aHandleError = [];

	/** @var array Destinatory */
	private $_aDests = [];

	/** @var string */
	private $_sSubject = 'Coercive\\FatalNotifyer Reporting System';

	/**
	 * DEFINE FATAL ERROR
	 *
	 * @return void
	 */
	private function _defineFatalError() {
		if(defined('E_FATAL')) { return; }
		define('E_FATAL',  E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);
	}

	/**
	 * THROW ERROR
	 *
	 * @param int $iSeverity
	 * @param string $sMessage
	 * @param string $sFileName
	 * @param int $iLine
	 * @param array $aContext [optional]
	 * @return void
	 * @throws ErrorException
	 */
	private function _throwError($iSeverity, $sMessage, $sFileName, $iLine, $aContext = []) {
		switch ($iSeverity) {
			/** Fatal run-time errors */
			case E_ERROR:
				throw new ErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Run-time warnings (non-fatal errors) */
			case E_WARNING:
				throw new WarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Compile-time parse errors */
			case E_PARSE:
				throw new ParseException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Run-time notices */
			case E_NOTICE:
				throw new NoticeException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Fatal errors that occur during PHP's initial startup */
			case E_CORE_ERROR:
				throw new CoreErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Warnings (non-fatal errors) that occur during PHP's initial startup */
			case E_CORE_WARNING:
				throw new CoreWarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Fatal compile-time errors (Zend) */
			case E_COMPILE_ERROR:
				throw new CompileErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Compile-time warnings (non-fatal errors) */
			case E_COMPILE_WARNING:
				throw new CoreWarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated error message */
			case E_USER_ERROR:
				throw new UserErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated warning message */
			case E_USER_WARNING:
				throw new UserWarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated notice message */
			case E_USER_NOTICE:
				throw new UserNoticeException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** PHP suggest changes to your code */
			case E_STRICT:
				throw new StrictException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Catchable fatal error */
			case E_RECOVERABLE_ERROR:
				throw new RecoverableErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Run-time notices */
			case E_DEPRECATED:
				throw new DeprecatedException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated warning message */
			case E_USER_DEPRECATED:
				throw new UserDeprecatedException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Unknown error */
			default:
				throw new ErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
		}
	}

	/**
	 * HANDLE ERROR
	 *
	 * @param int $iSeverity
	 * @return bool
	 */
	private function _isHandledError($iSeverity) {
		foreach ($this->_aHandleError as $iErrorType) {
			if($iSeverity & $iErrorType) {
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
	private function _handleError() {

		# Singleload
		static $bAlreadyPrepared = false;
		if($bAlreadyPrepared) { return; }
		$bAlreadyPrepared = true;

		# Set error handler
		set_error_handler([$this, 'errorHandler']);

	}

	/**
	 * HANDLE FATAL
	 *
	 * @return void
	 */
	private function _handleFatal() {

		# Singleload
		static $bAlreadyPrepared = false;
		if($bAlreadyPrepared) { return; }
		$bAlreadyPrepared = true;

		# Set fatal shutdown handler
		register_shutdown_function([$this, 'fatalHandler']);

	}

	/**
	 * FatalNotifyer constructor.
	 */
	public function __construct() {

		# const E_FATAL
		$this->_defineFatalError();

		# Report all errors
		error_reporting(E_ALL | E_STRICT);

	}

	/**
	 * DISPLAY ERROR
	 *
	 * PHP init display_errors status
	 *
	 * @param bool $bStatus
	 * @return $this
	 */
	public function displayError($bStatus) {
		ini_set('display_errors', $bStatus ? 'on' : 'off');
		return $this;
	}

	/**
	 * GET BACKTRACE
	 *
	 * @return string
	 */
	public function getBacktrace() {
		return (string) print_r(debug_backtrace(false), true);
	}

	/**
	 * STATIC : RESET ERROR REPORTING
	 *
	 * @return void
	 */
	static public function reset() {

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
	 * MAIN ERROR HANDLER
	 *
	 * @param int $iSeverity
	 * @param string $sMessage
	 * @param string $sFileName
	 * @param int $iLine
	 * @param array $aContext [optional]
	 * @return bool
	 * @throws ErrorException
	 */
	public function errorHandler($iSeverity, $sMessage, $sFileName, $iLine, $aContext = []) {

		# This error code is not included in error_reporting
		# Or error was suppressed with the '@' operator
		if (!(error_reporting() & $iSeverity)) { return false; }

		# Send mail if
		foreach($this->_aDests as $sEmail => $iErrorType) {
			if($iErrorType & $iSeverity) {
				(new FatalMailFormater)
					->setSubject($this->_sSubject)
					->setEmails(array_keys($this->_aDests))
					->setError($iSeverity, $sMessage, $sFileName, $iLine, $aContext, $this->getBacktrace())
					->send();
			}
		}

		# Throw if
		if(self::_isHandledError($iSeverity)) {
			$this->_throwError($iSeverity, $sMessage, $sFileName, $iLine, $aContext);
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
	 */
	public function fatalHandler() {

		# Retrieve last error
		$aError = error_get_last();
		if(!$aError) { return false; }

		# Handle fatal only
		if(isset($aError['type']) && ($aError['type'] & E_FATAL)) {
			$this->errorHandler($aError['type'], $aError['message'] ?? '', $aError['file'] ?? '', $aError['line'] ?? '');
		}

		# Don't execute PHP internal error handler
		return true;

	}

	/**
	 * AUTO TEST
	 *
	 * @return void
	 */
	static public function autoTest() {

		# (NOTICE)
		echo "----\nNOTICE\n";
		trigger_error('NOTICE', E_USER_NOTICE);

		# (WARNING)
		echo "----\nWARNING\n";
		trigger_error('WARNING', E_USER_WARNING);

		# (FATAL)
		echo "----\nFATAL\n";
		trigger_error('FATAL', E_USER_ERROR);

	}

	/**
	 * SET EMAIL SUBJECT
	 *
	 * @param string $sSubject
	 * @return $this
	 */
	public function setMailSubject($sSubject) {
		$this->_sSubject = (string) $sSubject;
		return $this;
	}

	/**
	 * ADD EMAIL
	 *
	 * @param array|string $mEmails
	 * @param int $iType [optional]
	 * @return $this
	 */
	public function mail($mEmails, $iType = E_ALL | E_STRICT) {

		# Emails list
		if(is_string($mEmails)) { $mEmails = [$mEmails]; }

		# Add Process
		foreach ($mEmails as $sEmail) {

			# Skip on error
			if(!filter_var($sEmail, FILTER_VALIDATE_EMAIL)) { continue; }

			# Set email
			$this->_aDests[(string) $sEmail] = $iType;

		}

		# Maintain chainability
		return $this;

	}

	/**
	 * REGISTER ERROR HANDLER
	 *
	 * @param int $iSeverity [optional]
	 * @return $this
	 */
	public function register($iSeverity = E_ALL | E_STRICT) {

		# SET SPECIFIC HANDLER
		$this->_aHandleError[] = $iSeverity;

		# SET HANDLERS
		$this->_handleError();
		if($iSeverity & E_FATAL) { $this->_handleFatal(); }

		# Maintain chainability
		return $this;

	}

}