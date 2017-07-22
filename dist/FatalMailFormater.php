<?php
namespace Coercive\Utility\FatalNotifyer;

use DateTime;

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
class FatalMailFormater {

	const COLORIZE = [
		'array' => 'cyan',
		'int' => 'cyan',
		'float' => 'cyan',
		'string' => 'cyan',

		'fatal' => 'red',
		'error' => 'red',
		'warning' => 'orange',
		'notice' => 'yellow',
	];

	/** @var string */
	private $_sDate = '';

	/** @var string */
	private $_sSubject = '---';

	/** @var array */
	private $_aEmails = [];

	/** @var int - Error Severity */
	private $_iSeverity = 0;

	/** @var string - Error Message */
	private $_sMessage = '';

	/** @var string - Error Filename */
	private $_sFileName = '';

	/** @var int - Error Line */
	private $_iLine = 0;

	/** @var array - Error Context */
	private $_aContext = [];

	/** @var string - Error Backtrace */
	private $_sBacktrace = '';

	/**
	 * MAIL HEADER
	 *
	 * @return string
	 */
	private function _getMailHeader() {
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
	private function _array($aArray) {
		return '<pre>' . print_r($aArray, true) . '</pre>';
	}

	/**
	 * COLORIZE
	 *
	 * @param string $sString
	 * @return mixed
	 */
	private function _colorize($sString) {

		foreach (self::COLORIZE as $sItem => $sColor) {
			$sString = preg_replace("`$sItem`i", "<span style='color:$sColor;font-weight:bold'>$sItem</span>", $sString);
		}

		return $sString;

	}

	/**
	 * HTML ERROR
	 *
	 * Transform error datas to HTML
	 *
	 * @return string
	 */
	private function _htmlError() {
		return "
			<table>
				<thead>
					<th style='background-color: black;color: white; font-weight: bold'>ITEM</th>
					<th style='background-color: black;color: white; font-weight: bold'>Description</th>
				</thead>
				<tbody>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Error</th>
						<td><pre>{$this->_colorize($this->_sMessage)}</pre></td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Errno</th>
						<td><pre>$this->_iSeverity</pre></td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>File</th>
						<td style='background-color:yellowgreen;font-weight:bold;color:black'>$this->_sFileName</td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Line</th>
						<td>$this->_iLine</td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Context</th>
						<td style='background-color: #e8e8e8'>{$this->_colorize($this->_array($this->_aContext))}</td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Trace</th>
						<td style='background-color: #d3d3d3'><pre>{$this->_colorize($this->_sBacktrace)}</pre></td>
					</tr>
				</tbody>
			</table>";
	}

	/**
	 * HTML FULL MESSAGE
	 *
	 * @return string
	 */
	private function _htmlFullMessage() {
		return
			"<b><u>DATE :</u></b><br />{$this->_sDate}<br /><br />" .
			"<b><u>ERROR :</u></b><br />{$this->_htmlError()}" .
			'<br /><hr /><br /><br />' .
			'<b><u>SERVER :</u></b><br /><div style="background-color:#f0f0f0">' . $this->_array($_SERVER) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>GET :</u></b><br /><div style="background-color:#ebebeb">' . $this->_array($_GET) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>POST :</u></b><br /><div style="background-color:#e6e6e6">' . $this->_array($_POST) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>FILE :</u></b><br /><div style="background-color:#e1e1e1">' . $this->_array($_FILES) . '</div>';
	}

	/**
	 * FatalMailFormater constructor.
	 */
	public function __construct() {
		$this->_sDate = (new DateTime)->format('Y-m-d H:i:s');
	}

	/**
	 * SET SUBJECT
	 *
	 * @param string $sSubject
	 * @return $this
	 */
	public function setSubject($sSubject) {
		$this->_sSubject = (string) $sSubject;
		return $this;
	}

	/**
	 * SET EMAILS
	 *
	 * @param array $aEmails
	 * @return $this
	 */
	public function setEmails($aEmails) {
		$this->_aEmails = (array) $aEmails;
		return $this;
	}

	/**
	 * SET ERROR
	 *
	 * @param int $iSeverity
	 * @param string $sMessage
	 * @param string $sFileName
	 * @param int $iLine
	 * @param array $aContext
	 * @param string $sBacktrace
	 * @return $this
	 */
	public function setError($iSeverity, $sMessage, $sFileName, $iLine, $aContext, $sBacktrace) {
		$this->_iSeverity = (int) $iSeverity;
		$this->_sMessage = (string) $sMessage;
		$this->_sFileName = (string) $sFileName;
		$this->_iLine = (int) $iLine;
		$this->_aContext = (array) $aContext;
		$this->_sBacktrace = (string) $sBacktrace;
		return $this;
	}

	/**
	 * SEND EMAIL
	 *
	 * @return $this
	 */
	public function send() {
		foreach ($this->_aEmails as $sEmail) {
			mail($sEmail, $this->_sSubject, $this->_htmlFullMessage(), $this->_getMailHeader());
		}
		return $this;
	}

}