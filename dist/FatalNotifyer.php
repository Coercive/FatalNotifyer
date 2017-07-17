<?php
namespace Coercive\Utility\FatalNotifyer;

use DateTime;
use ErrorException;

/**
 * FatalNotifyer
 * PHP Version 7
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

	/** @var bool Handlers */
	static private 	$_aHandleError = [];

	/** @var array */
	static private $_aDests = [];

	/** @var string */
	static private $_sSubject = 'Coercive\\FatalNotifyer Reporting System';

	/**
	 * SCALE BY LOG
	 *
	 * Auto test error handling system
	 * @link http://php.net/manual/fr/function.set-error-handler.php
	 *
	 * @param array $vect
	 * @param int|float $scale
	 * @return array|null
	 */
	static private function _scaleByLog($vect, $scale) {

		if (!is_numeric($scale) || $scale <= 0) {
			trigger_error("log(x) for x <= 0 is undefined, you used: scale = $scale", E_USER_ERROR);
		}

		if (!is_array($vect)) {
			trigger_error("Incorrect entrie type, waiting for array of values", E_USER_WARNING);
			return null;
		}

		$temp = [];
		foreach($vect as $pos => $value) {
			if (!is_numeric($value)) {
				trigger_error("The position value $pos is not number, 0 (zero) used", E_USER_NOTICE);
				$value = 0;
			}
			$temp[$pos] = log($scale) * $value;
		}
		return $temp;

	}

	/**
	 * DEFINE FATAL ERROR
	 *
	 * @return void
	 */
	static private function _defineFatalError() {
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
	 * @throws ErrorException
	 */
	static private function _throwError($iSeverity, $sMessage, $sFileName, $iLine, $aContext = []) {
		switch ($iSeverity) {
			/** Fatal run-time errors */
			case E_ERROR:
				throw new ErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Run-time warnings (non-fatal errors) */
			case E_WARNING:
				throw new Exceptions\WarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Compile-time parse errors */
			case E_PARSE:
				throw new Exceptions\ParseException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Run-time notices */
			case E_NOTICE:
				throw new Exceptions\NoticeException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Fatal errors that occur during PHP's initial startup */
			case E_CORE_ERROR:
				throw new Exceptions\CoreErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Warnings (non-fatal errors) that occur during PHP's initial startup */
			case E_CORE_WARNING:
				throw new Exceptions\CoreWarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Fatal compile-time errors (Zend) */
			case E_COMPILE_ERROR:
				throw new Exceptions\CompileErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Compile-time warnings (non-fatal errors) */
			case E_COMPILE_WARNING:
				throw new Exceptions\CoreWarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated error message */
			case E_USER_ERROR:
				throw new Exceptions\UserErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated warning message */
			case E_USER_WARNING:
				throw new Exceptions\UserWarningException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated notice message */
			case E_USER_NOTICE:
				throw new Exceptions\UserNoticeException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** PHP suggest changes to your code */
			case E_STRICT:
				throw new Exceptions\StrictException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Catchable fatal error */
			case E_RECOVERABLE_ERROR:
				throw new Exceptions\RecoverableErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Run-time notices */
			case E_DEPRECATED:
				throw new Exceptions\DeprecatedException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** User-generated warning message */
			case E_USER_DEPRECATED:
				throw new Exceptions\UserDeprecatedException($sMessage, 0, $iSeverity, $sFileName, $iLine);
			/** Unknown error */
			default:
				throw new ErrorException($sMessage, 0, $iSeverity, $sFileName, $iLine);
		}
	}

	/**
	 * HTML ERROR
	 *
	 * Transform error datas to HTML
	 *
	 * @param int $iSeverity
	 * @param string $sMessage
	 * @param string $sFileName
	 * @param int $iLine
	 * @return string
	 */
	static private function _htmlError($iSeverity, $sMessage, $sFileName, $iLine, $aContext) {
		return "
			<table>
				<thead>
					<th>Item</th>
					<th>Description</th>
				</thead>
				<tbody>
					<tr>
						<th>Error</th>
						<td><pre>$sMessage</pre></td>
					</tr>
					<tr>
						<th>Errno</th>
						<td><pre>$iSeverity</pre></td>
					</tr>
					<tr>
						<th>File</th>
						<td>$sFileName</td>
					</tr>
					<tr>
						<th>Line</th>
						<td>$iLine</td>
					</tr>
					<tr>
						<th>Context</th>
						<td>" . self::_array($aContext) . "</td>
					</tr>
					<tr>
						<th>Trace</th>
						<td><pre>" . self::getBacktrace() . "</pre></td>
					</tr>
				</tbody>
			</table>";
	}

	/**
	 * MAIL HEADER
	 *
	 * @return string
	 */
	static private function _getMailHeader() {
		return
			"MIME-Version: 1.0\r\n" .
			"Content-type: text/html; charset=UTF-8\r\n";
	}

	/**
	 * TO STRING
	 *
	 * @param array $aArray
	 * @return string
	 */
	static private function _array($aArray) {
		return '<pre>' . print_r($aArray, true) . '</pre>';
	}

	/**
	 * HTML FULL MESSAGE
	 *
	 * @param string $sError
	 * @return string
	 */
	static private function _htmlFullMessage($sError) {
		$sDate = (new DateTime)->format('Y-m-d H:i:s');
		return
			"<b><u>DATE :</u></b> {$sDate}<br />" .
			"<b><u>ERROR :</u></b><br />{$sError}" .
			"<br /><hr /><br /><br />" .
			"<b><u>SERVER :</u></b><br />" . self::_array($_SERVER) .
			"<br /><hr /><br /><br />" .
			"<b><u>GET :</u></b><br />" . self::_array($_GET) .
			"<br /><hr /><br /><br />" .
			"<b><u>POST :</u></b><br />" . self::_array($_POST) .
			"<br /><hr /><br /><br />" .
			"<b><u>FILE :</u></b><br />" . self::_array($_FILES) . "<br />";
	}

	/**
	 * SEND EMAIL
	 *
	 * @param string $sBody
	 * @return void
	 */
	static private function _mail($sBody) {
		foreach (self::$_aDests as $sEmail) {
			mail($sEmail, self::$_sSubject, $sBody, self::_getMailHeader());
		}
	}

	/**
	 * HANDLE ERROR
	 *
	 * @param int $iSeverity
	 * @return bool
	 */
	static private function _isHandledError($iSeverity) {
		foreach (self::$_aHandleError as $aHandler) {
			if($iSeverity & $aHandler['severity']) {
				return $aHandler['handle'];
			}
		}
	}

	/**
	 * EMAILABLE ERROR
	 *
	 * @param int $iSeverity
	 * @return bool
	 */
	static private function _isEmailableError($iSeverity) {
		foreach (self::$_aHandleError as $aHandler) {
			if($iSeverity & $aHandler['severity']) {
				return $aHandler['mail'];
			}
		}
	}

	/**
	 * HANDLE ERROR
	 *
	 * @return void
	 */
	static private function _handleError() {

		# Singleload
		static $bAlreadyPrepared = false;
		if($bAlreadyPrepared) { return; }
		$bAlreadyPrepared = true;

		# Set error handler
		set_error_handler([__CLASS__, 'errorHandler']);

	}

	/**
	 * HANDLE FATAL
	 *
	 * @return void
	 */
	static private function _handleFatal() {

		# Singleload
		static $bAlreadyPrepared = false;
		if($bAlreadyPrepared) { return; }
		$bAlreadyPrepared = true;

		# Set fatal shutdown handler
		register_shutdown_function([__CLASS__, 'fatalHandler']);

	}

	/**
	 * INIT BASICS
	 *
	 * @return void
	 */
	static public function init() {

		# FATAL
		self::_defineFatalError();

		# Report all errors
		error_reporting(E_ALL | E_STRICT);

	}

	/**
	 * DISPLAY ERROR
	 *
	 * PHP init display_errors status
	 *
	 * @param bool $bStatus
	 * @return void
	 */
	static public function displayError($bStatus) {
		ini_set('display_errors', $bStatus ? 'on' : 'off');
	}

	/**
	 * GET BACKTRACE
	 *
	 * @return string
	 */
	static public function getBacktrace() {
		return (string) print_r(debug_backtrace(false), true);
	}

	/**
	 * RESET ERROR REPORTING
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
		error_reporting(E_ALL);

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
	static public function errorHandler($iSeverity, $sMessage, $sFileName, $iLine, $aContext = []) {

		# This error code is not included in error_reporting
		# Or error was suppressed with the '@' operator
		if (!(error_reporting() & $iSeverity)) { return false; }

		# Send mail if
		if(self::_isEmailableError($iSeverity)) {
			$sBody = self::_htmlFullMessage(self::_htmlError($iSeverity, $sMessage, $sFileName, $iLine, $aContext));
			self::_mail($sBody);
		}

		# Throw if
		if(self::_isHandledError($iSeverity)) {
			self::_throwError($iSeverity, $sMessage, $sFileName, $iLine, $aContext);
		}

		# Don't execute PHP internal error handler
		return true;

	}

	/**
	 * FATAL ERROR HANDLER
	 *
	 * Redirect to classical errorHandler
	 *
	 * @return void
	 */
	static public function fatalHandler() {

		# Retrieve last error
		$aError = error_get_last();
		if(!$aError) { return; }

		# Handle fatal only
		if(isset($aError['type']) && ($aError['type'] & E_FATAL)) {
			static::errorHandler($aError['type'], $aError['message'] ?? '', $aError['file'] ?? '', $aError['line'] ?? '');
		}

	}

	/**
	 * AUTO TEST
	 *
	 * @return void
	 */
	static public function autoTest() {

		# Generate sample errors
		echo "----\nvector a\n";
		$a = [2, 3, 'foo', 5.5, 43.3, 21.11];
		print_r($a);

		# (NOTICE) Number error
		echo "----\nvector b - a notice (b = log(PI) * a)\n";
		$b = self::_scaleByLog($a, M_PI);
		print_r($b);

		# (WARNING) Type error
		echo "----\nvector c - a warning\n";
		$c = self::_scaleByLog('not an array', 2.3);
		var_dump($c); // NULL

		# (FATAL) Logarythme of zero or negative
		echo "----\nvector d - fatal error\n";
		$d = self::_scaleByLog($a, -2.5);

	}

	/**
	 * SET EMAIL SUBJECT
	 *
	 * @param string $sSubject
	 * @return void
	 */
	static public function setMailSubject($sSubject) {
		self::$_sSubject = (string) $sSubject;
	}

	/**
	 * ADD EMAIL DEST
	 *
	 * @param string $sEmail
	 * @return bool
	 */
	static public function addMailDest($sEmail) {

		# Skip on error
		if(!filter_var($sEmail, FILTER_VALIDATE_EMAIL)) { return false; }

		# Set email
		self::$_aDests[] = (string) $sEmail;

		# Ok status
		return true;

	}

	/**
	 * REGISTER ERROR HANDLER
	 *
	 * @param int $iSeverity
	 * @param bool $bHandleError [optional]
	 * @param bool $bSendByMail [optional]
	 * @return void
	 */
	static public function registerError($iSeverity, $bHandleError = false, $bSendByMail = false) {

		# SET SPECIFIC HANDLER
		self::$_aHandleError[] = ['severity' => $iSeverity, 'handle' => (bool) $bHandleError, 'mail' => (bool) $bSendByMail];

		# SET HANDLERS
		if($iSeverity & E_FATAL) { self::_handleFatal(); }
		else { self::_handleError(); }

	}

}