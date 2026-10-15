<?php
declare(strict_types=1);

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
 * @copyright   2017 - 2026 Anthony Moral
 * @license 	MIT
 */
final class FatalLog
{
	const SEPARATOR = "##########----------LOGSEPARATOR----------##########\r\n";

	private string $directory;

	/**
	 * @param string $directory
	 * @return string
	 * @throws Exception
	 */
	private function createDirectory(string $directory): string
	{
		# Already exist
		if(is_dir($directory) && ($path = realpath($directory))) {
			return $path;
		}

		if(!@mkdir($directory, 0755, true) && !is_dir($directory)) {
			throw new Exception("Can't create directory in $directory");
		}
		return (string) realpath($directory);
	}

	/**
	 * Create a sub directory like /path/0000-00-00
	 *
	 * @param string $directory
	 * @return string
	 * @throws Exception
	 */
	private function createSubDir(string $directory): string
	{
		$path = $this->directory . DIRECTORY_SEPARATOR . $directory;
		if(!is_dir($path)) {
			$this->createDirectory($path);
		}

		$real = realpath($path);
		if(!$real || !is_dir($real)) {
			throw new Exception("Is not directory in $path");
		}
		return $real;
	}

	/**
	 * @param array $array
	 * @return string
	 */
	private function printArray(array $array): string
	{
		return '<pre>' . print_r($array, true) . '</pre>';
	}

	/**
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
		return (string) json_encode([
			'severity' => $severity,
			'message' => $message,
			'filename' => $fileName,
			'line' => $line,
			'context' => $context,
			'backtrace' => $backtrace,

			'GET' => $this->printArray($_GET ?? []),
			'POST' => $this->printArray($_POST ?? []),
			'FILE' => $this->printArray($_FILES ?? []),
			'SERVER' => $this->printArray($_SERVER ?? []),
			'SESSION' => $this->printArray($_SESSION ?? []),
		], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
	}

	/**
	 * @param string $log
	 * @param string $path
	 * @param string $file
	 * @return bool
	 */
	private function write(string $log, string $path, string $file): bool
	{
		return false !== @file_put_contents($path . DIRECTORY_SEPARATOR . $file, self::SEPARATOR . $log . "\r\n", FILE_APPEND | LOCK_EX);
	}

	/**
	 * Log file to array
	 *
	 * @param string $path
	 * @return array
	 */
	private function read(string $path): array
	{
		if(!is_file($path) || !($fp = @fopen($path, 'r'))) {
			return [];
		}

		$results = [];
		$i = 0;
		while(false !== ($line = fgets($fp))) {
			if(strpos($line, self::SEPARATOR) === 0) {
				$i++;
				continue;
			}
			$results[$i] = json_decode($line, true);
		}
		fclose($fp);

		return $results;
	}

	/**
	 * FatalLog constructor.
	 *
	 * @param string $directory Path
	 * @throws Exception
	 */
	public function __construct(string $directory)
	{
		$this->directory = $this->createDirectory($directory);
		if(!$this->directory) {
			throw new Exception("Is not directory in $directory");
		}
	}

	/**
	 * @param int $severity
	 * @param string $message
	 * @param string $fileName
	 * @param int $line
	 * @param array $context
	 * @param string $backtrace
	 * @return $this
	 * @throws Exception
	 */
	public function save(int $severity, string $message, string $fileName, int $line, array $context, string $backtrace): self
	{
		$date = new DateTime;
		$directory = $this->createSubDir($date->format('Y-m-d'));

		$log = $this->formatError($severity, $message, $fileName, $line, $context, $backtrace);
		if(!$this->write($log, $directory, $date->format('H_i_s'))) {
			throw new Exception("Can't write log datas.");
		}
		return $this;
	}

	/**
	 * Errors of a log file
	 *
	 * @param string $file Full path
	 * @return array
	 */
	public function getFile(string $file): array
	{
		return $this->read($file);
	}

	/**
	 * Files of a day directory
	 *
	 * @param string $day Like '2017-07-22'
	 * @return string[]
	 */
	public function getFileList(string $day): array
	{
		$path = $this->directory . DIRECTORY_SEPARATOR . $day;
		if(!is_dir($path)) {
			return [];
		}

		$list = [];
		foreach (new DirectoryIterator($path) as $file) {
			if ($file->isFile()) {
				$list[] = $file->getFilename();
			}
		}
		sort($list);
		return $list;
	}

	/**
	 * Days directories
	 *
	 * @return string[]
	 */
	public function getDayList(): array
	{
		$list = [];
		foreach (new DirectoryIterator($this->directory) as $file) {
			if (!$file->isDot() && $file->isDir()) {
				$list[] = $file->getFilename();
			}
		}
		rsort($list);
		return $list;
	}
}
