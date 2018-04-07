<?php
namespace Coercive\Utility\FatalNotifyer;

use DateTime;
use Exception;
use DirectoryIterator;

/**
 * FatalLog
 *
 * @package 	Coercive\Utility\FatalNotifyer
 * @link		https://github.com/Coercive/FatalNotifyer
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2017 - 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class FatalLog
{
	/** @var string */
	private $directory = '';

	/**
	 * CREATE DIRECTORY
	 *
	 * @param string $directory
	 * @return string
	 * @throws Exception
	 */
	private function createDirectory(string $directory): string
	{
		# Already exist
		if(is_dir($directory) && ($path = realpath($directory))) { return $path; }

		# Create
		if(!@mkdir($directory, 0755, true)) {
			throw new Exception("Can't create directory in $directory");
		}

		# Return path
		return (string) realpath($directory);
	}

	/**
	 * CREATE SUBDIR
	 *
	 * @param string $directory
	 * @return string
	 * @throws Exception
	 */
	private function createSubDir(string $directory): string
	{
		# Prepare sub dir like /path/0000-00-00
		$path = $this->directory . DIRECTORY_SEPARATOR . $directory;
		if(!is_dir($path)) { $this->createDirectory($path); }

		# Verify
		$real = realpath($path);
		if(!$real || !is_dir($real)) { throw new Exception("Is not directory in $path"); }

		# Return path
		return $real;
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
	 * GET LOG SEPARATOR
	 *
	 * @return string
	 */
	private function getLogSeparator(): string
	{
		return "##########----------LOGSEPARATOR----------##########\r\n";
	}

	/**
	 * FORMAT ERROR
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @param array $context
	 * @param string $backtrace
	 * @return string
	 */
	private function formatError(int $severity, string $message, string $fileName, int $line, array $context, string $backtrace): string
	{
		try {
			$log = json_encode([
				'severity' => $severity,
				'message' => $message,
				'filename' => $fileName,
				'line' => $line,
				'context' => $context,
				'backtrace' => $backtrace,

				'GET' => $this->printArray($_SERVER ?? []),
				'POST' => $this->printArray($_POST ?? []),
				'FILE' => $this->printArray($_FILES ?? []),
				'SERVER' => $this->printArray($_SERVER ?? []),
				'SESSION' => $this->printArray($_SESSION ?? [])
			]);
		}
		catch (Exception $e) {
			$log = json_encode([
				'severity' => $e->getCode(),
				'message' => $e->getMessage(),
				'filename' => $e->getFile(),
				'line' => $e->getLine(),
				'context' => $e->getTrace(),
				'backtrace' => $e->getTraceAsString()
			]);
		}

		return $log;
	}

	/**
	 * WRITE LOG
	 *
	 * @param string $log
	 * @param string $path
	 * @param string $file
	 * @return bool
	 */
	private function write(string $log, string $path, string $file): bool
	{
		try {
			# Open or create file
			$fp = fopen($path . DIRECTORY_SEPARATOR . $file,'a+');
			if(!$fp) { throw new Exception("Can't create log file in " . $path . DIRECTORY_SEPARATOR . $file); }

			# Place pointer at the end
			fseek($fp,SEEK_END);

			# Prepare new line text
			$line = "{$this->getLogSeparator()}{$log}\r\n";

			# Save in log file
			fputs($fp, $line);

			# Close log file
			fclose($fp);

			# ok status
			return true;
		}
		catch (Exception $e) { return false; }
	}

	/**
	 * LOG FILE TO ARRAY
	 *
	 * @param string $path
	 * @return array
	 */
	private function read(string $path): array
	{
		# No file
		if(!is_file($path)) { return []; }

		try {
			# Open file
			$fp = fopen($path, 'r+');

			# Tranform all lines un array
			$results = []; $i = 0;
			while($line = fgets($fp)) {
				if(strpos($line,  $this->getLogSeparator()) === 0) { $i++; continue; }
				$results[$i] = json_decode($line, true);
			}

			# Close log file
			fclose($fp);

			# Datas
			return $results;
		}
		catch (Exception $e) { return []; }
	}

	/**
	 * FatalLog constructor.
	 *
	 * @param string $directory Path
	 * @throws Exception
	 */
	public function __construct(string $directory)
	{
		$this->directory = $this->createDirectory($directory);;
		if(!$this->directory) { throw new Exception("Is not directory in $directory"); }
	}

	/**
	 * SAVE LOG
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @param array $context
	 * @param string $backtrace
	 * @return FatalLog
	 * @throws Exception
	 */
	public function save(int $severity, string $message, string $fileName, int $line, array $context, string $backtrace): FatalLog
	{
		# Prepare subdir
		$date = new DateTime;
		$directory = $this->createSubDir($date->format('Y-m-d'));
		if(!$directory) { throw new Exception("Can't create subdirectory."); }

		# Write log
		$log = $this->formatError($severity, $message, $fileName, $line, $context, $backtrace);
		$status = $this->write($log, $directory, $date->format('H_i_s'));
		if(!$status) { throw new Exception("Can't write log datas."); }

		return $this;
	}

	/**
	 * GET FILE DATAS
	 *
	 * @param string $file
	 * @return array
	 */
	public function getFile(string $file): array
	{
		return $this->read($file);
	}

	/**
	 * GET FILES LIST
	 *
	 * @param string $day
	 * @return array
	 */
	public function getFileList(string $day): array
	{
		# Init list
		$list = [];

		# Parse directory
		$files = new DirectoryIterator($this->directory . DIRECTORY_SEPARATOR . $day);
		foreach ($files as $file) {
			if ($file->isFile()) {
				$list[] = $file->getFilename();
			}
		}

		# Datas
		return $list;
	}

	/**
	 * GET DAYS LIST
	 *
	 * @return array
	 */
	public function getDayList(): array
	{
		# Init list
		$list = [];

		# Parse directory
		$dir = new DirectoryIterator($this->directory);
		foreach ($dir as $file) {
			if (!$file->isDot() && $file->isDir()) {
				$list[] = $file->getFilename();
			}
		}

		# Datas
		return $list;
	}
}