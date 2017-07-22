<?php
namespace Coercive\Utility\FatalNotifyer;

use DateTime;
use Exception;
use DirectoryIterator;

/**
 * FatalLog
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
class FatalLog {

	/** @var string */
	private $_sDirectory = '';

	/**
	 * CREATE DIRECTORY
	 *
	 * @param string $sDirectory
	 * @return string
	 * @throws Exception
	 */
	private function _createDirectory($sDirectory) {

		# Already exist
		if(is_dir($sDirectory) && ($sRealDir = realpath($sDirectory))) { return $sRealDir; }

		# Create
		if(!@mkdir($sDirectory, 0755, true)) {
			throw new Exception("Can't create directory in $sDirectory");
		}

		# Return path
		return (string) realpath($sDirectory);

	}

	/**
	 * CREATE SUBDIR
	 *
	 * @param string $sSubDirName
	 * @return string
	 * @throws Exception
	 */
	private function _createSubDir($sSubDirName) {

		# Prepare sub dir like /path/0000-00-00
		$sSubDir = $this->_sDirectory . DIRECTORY_SEPARATOR . $sSubDirName;
		if(!is_dir($sSubDir)) { $this->_createDirectory($sSubDir); }

		# Verify
		$sRealSubDir = realpath($sSubDir);
		if(!$sRealSubDir) { throw new Exception("Is not directory in $sSubDir"); }

		# Return path
		return $sRealSubDir;

	}

	/**
	 * GET LOG SEPARATOR
	 *
	 * @return string
	 */
	private function _getLogSeparator() {
		return "----------LOGSEPARATOR_". __CLASS__ . "_##########\r\n";
	}

	/**
	 * FORMAT ERROR
	 *
	 * @param int $iSeverity
	 * @param string $sMessage
	 * @param string $sFileName
	 * @param int $iLine
	 * @param array $aContext
	 * @param string $sBacktrace
	 * @return string
	 */
	private function _formatError($iSeverity, $sMessage, $sFileName, $iLine, $aContext, $sBacktrace) {

		try {
			$sLog = json_encode([
				'severity' => $iSeverity,
				'message' => $sMessage,
				'filename' => $sFileName,
				'line' => $iLine,
				'context' => $aContext,
				'backtrace' => $sBacktrace
			]);
		}
		catch (Exception $oException) {
			$sLog = json_encode([
				'severity' => $oException->getCode(),
				'message' => $oException->getMessage(),
				'filename' => $oException->getFile(),
				'line' => $oException->getLine(),
				'context' => $oException->getTrace(),
				'backtrace' => $oException->getTraceAsString()
			]);
		}

		return $sLog;

	}

	/**
	 * WRITE LOG
	 *
	 * @param string $sLog
	 * @param string $sPath
	 * @param string $sFile
	 * @return bool
	 */
	private function _write($sLog, $sPath, $sFile) {

		try {

			# Open or create file
			$rFp = fopen($sPath . DIRECTORY_SEPARATOR . $sFile,'a+');
			if(!$rFp) { throw new Exception("Can't create log file in " . $sPath . DIRECTORY_SEPARATOR . $sFile); }

			# Place pointer at the end
			fseek($rFp,SEEK_END);

			# Prepare new line text
			$sNewLine = "{$this->_getLogSeparator()}{$sLog}\r\n";

			# Save in log file
			fputs($rFp, $sNewLine);

			# Close log file
			fclose($rFp);

			# ok status
			return true;

		}
		catch (Exception $oException) { return false; }

	}

	/**
	 * LOG FILE TO ARRAY
	 *
	 * @param string $sFilePath
	 * @return array
	 */
	private function _read($sFilePath) {

		# No file
		if(!is_file($sFilePath)) { return []; }

		try {

			# Open file
			$rFp = fopen($sFilePath, 'r+');

			# Tranform all lines un array
			$aResults = []; $i = 0;
			while($sLine = fgets($rFp)) {
				if(strpos($sLine,  $this->_getLogSeparator()) === 0) { $i++; continue; }
				$aResults[$i] = json_decode($sLine, true);
			}

			# Close log file
			fclose($rFp);

			# Datas
			return $aResults;

		}
		catch (Exception $oException) { return []; }

	}

	/**
	 * FatalLog constructor.
	 *
	 * @param string $sDirectory Path
	 * @throws Exception
	 */
	public function __construct($sDirectory) {

		# Set Dir
		$this->_sDirectory = $this->_createDirectory($sDirectory);;
		if(!$this->_sDirectory) { throw new Exception("Is not directory in $sDirectory"); }

	}

	/**
	 * SAVE LOG
	 *
	 * @param int $iSeverity
	 * @param string $sMessage
	 * @param string $sFileName
	 * @param int $iLine
	 * @param array $aContext
	 * @param string $sBacktrace
	 * @return void
	 * @throws Exception
	 */
	public function save($iSeverity, $sMessage, $sFileName, $iLine, $aContext, $sBacktrace) {

		# Prepare subdir
		$oDate = new DateTime;
		$sSubDir = $this->_createSubDir($oDate->format('Y-m-d'));
		if(!$sSubDir) { throw new Exception("Can't create subdirectory."); }

		# Write log
		$sLog = $this->_formatError($iSeverity, $sMessage, $sFileName, $iLine, $aContext, $sBacktrace);
		$bStatus = $this->_write($sLog, $sSubDir, $oDate->format('H_i_s'));
		if(!$bStatus) { throw new Exception("Can't write log datas."); }

	}

	/**
	 * GET FILE DATAS
	 *
	 * @param string $sFile
	 * @return array
	 */
	public function getFile($sFile) {
		return $this->_read($sFile);
	}

	/**
	 * GET FILES LIST
	 *
	 * @param string $sDay
	 * @return array
	 */
	public function getFileList($sDay) {

		# Init list
		$aList = [];

		# Parse directory
		$oFiles = new DirectoryIterator($this->_sDirectory . DIRECTORY_SEPARATOR . $sDay);
		foreach ($oFiles as $oFile) {
			if ($oFile->isFile()) {
				$aList[] = $oFile->getFilename();
			}
		}

		# Datas
		return $aList;

	}

	/**
	 * GET DAYS LIST
	 *
	 * @return array
	 */
	public function getDayList() {

		# Init list
		$aList = [];

		# Parse directory
		$oDir = new DirectoryIterator($this->_sDirectory);
		foreach ($oDir as $oFile) {
			if (!$oFile->isDot() && $oFile->isDir()) {
				$aList[] = $oFile->getFilename();
			}
		}

		# Datas
		return $aList;

	}

}