<?php
namespace Coercive\Utility\FatalNotifyer;

use DateTime;

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
class FatalMailFormater
{
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

	/** @var bool */
	private $notify = false;

	/** @var string */
	private $date = '';

	/** @var string */
	private $subject = '---';

	/** @var array */
	private $emails = [];

	/** @var int - Error Severity */
	private $severity = 0;

	/** @var string - Error Message */
	private $message = '';

	/** @var string - Error Filename */
	private $fileName = '';

	/** @var int - Error Line */
	private $line = 0;

	/** @var array - Error Context */
	private $context = [];

	/** @var string - Error Backtrace */
	private $backtrace = '';

	/**
	 * MAIL HEADER
	 *
	 * @return string
	 */
	private function _getMailHeader(): string
	{
		return
			"MIME-Version: 1.0\r\n" .
			"Content-type: text/html; charset=UTF-8\r\n";
	}

	/**
	 * TO STRING
	 *
	 * @param array $array
	 * @return string
	 */
	private function printArray(array $array): string
	{
		return '<pre>' . print_r($array, true) . '</pre>';
	}

	/**
	 * COLORIZE
	 *
	 * @param string $string
	 * @return string
	 */
	private function colorize(string $string): string
	{
		foreach (self::COLORIZE as $item => $color) {
			$string = preg_replace("`$item`i", "<span style='color:$color;font-weight:bold'>$item</span>", $string);
		}
		return $string;
	}

	/**
	 * HTML ERROR
	 *
	 * Transform error datas to HTML
	 *
	 * @return string
	 */
	private function htmlError():string
	{
		return "
			<table>
				<thead>
					<th style='background-color: black;color: white; font-weight: bold'>ITEM</th>
					<th style='background-color: black;color: white; font-weight: bold'>Description</th>
				</thead>
				<tbody>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Error</th>
						<td><pre>{$this->colorize($this->message)}</pre></td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Errno</th>
						<td><pre>$this->severity</pre></td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>File</th>
						<td style='background-color:yellowgreen;font-weight:bold;color:black'>$this->fileName</td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Line</th>
						<td>$this->line</td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Context</th>
						<td style='background-color: #e8e8e8'>{$this->colorize($this->printArray($this->context))}</td>
					</tr>
					<tr>
						<th style='background-color: black;color: white; font-weight: bold'>Trace</th>
						<td style='background-color: #d3d3d3'><pre>{$this->colorize($this->backtrace)}</pre></td>
					</tr>
				</tbody>
			</table>";
	}

	/**
	 * HTML FULL MESSAGE
	 *
	 * @return string
	 */
	private function htmlFullMessage(): string
	{
		# Notify Only
		if($this->notify) {
			return
				"<b><u>DATE :</u></b><br />{$this->date}<br /><br />" .
				'<br /><hr /><br /><br />' .
				"<b><u>ERROR :</u></b><br />{$this->message}<br />";
		}

		# Full email
		return
			"<b><u>DATE :</u></b><br />{$this->date}<br /><br />" .
			"<b><u>ERROR :</u></b><br />{$this->htmlError()}" .
			'<br /><hr /><br /><br />' .
			'<b><u>SERVER :</u></b><br /><div style="background-color:#f0f0f0">' . $this->printArray($_SERVER ?? []) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>GET :</u></b><br /><div style="background-color:#ebebeb">' . $this->printArray($_GET ?? []) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>POST :</u></b><br /><div style="background-color:#e6e6e6">' . $this->printArray($_POST ?? []) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>FILE :</u></b><br /><div style="background-color:#e1e1e1">' . $this->printArray($_FILES ?? []) . '</div>' .
			'<br /><hr /><br /><br />' .
			'<b><u>SESSION :</u></b><br /><div style="background-color:#d4d4d4">' . $this->printArray($_SESSION ?? []) . '</div>';
	}

	/**
	 * FatalMailFormater constructor.
	 */
	public function __construct()
	{
		$this->date = (new DateTime)->format('Y-m-d H:i:s');
	}

	/**
	 * SET SUBJECT
	 *
	 * @param string $subject
	 * @return $this
	 */
	public function setSubject(string $subject): FatalMailFormater
	{
		$this->subject = $subject;
		return $this;
	}

	/**
	 * SET EMAILS
	 *
	 * @param array $emails
	 * @return $this
	 */
	public function setEmails(array $emails): FatalMailFormater
	{
		$this->emails = $emails;
		return $this;
	}

	/**
	 * SET NOTIFY ONLY
	 *
	 * @param string $message
	 * @return $this
	 */
	public function setNotifyOnly(string $message): FatalMailFormater
	{
		$this->notify = true;
		$this->message = $message;
		return $this;
	}

	/**
	 * SET ERROR
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @param array $context
	 * @param string $backtrace
	 * @return $this
	 */
	public function setError(int $severity, string $message, string $fileName, int $line, array $context, string $backtrace): FatalMailFormater
	{
		$this->severity = $severity;
		$this->message = $message;
		$this->fileName = $fileName;
		$this->line = $line;
		$this->context = $context;
		$this->backtrace = $backtrace;
		return $this;
	}

	/**
	 * SEND EMAIL
	 *
	 * @return $this
	 */
	public function send(): FatalMailFormater
	{
		foreach ($this->emails as $email) {
			mail($email, $this->subject, $this->htmlFullMessage(), $this->_getMailHeader());
		}
		return $this;
	}
}