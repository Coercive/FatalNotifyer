<?php
namespace Coercive\Utility\FatalNotifyer\Tests;

use Coercive\Utility\FatalNotifyer\Exceptions\CompileWarningException;
use Coercive\Utility\FatalNotifyer\Exceptions\StrictException;
use Coercive\Utility\FatalNotifyer\Exceptions\UserNoticeException;
use Coercive\Utility\FatalNotifyer\Exceptions\UserWarningException;
use Coercive\Utility\FatalNotifyer\Exceptions\WarningException;
use Coercive\Utility\FatalNotifyer\FatalLog;
use Coercive\Utility\FatalNotifyer\FatalNotifyer;
use ErrorException;
use PHPUnit\Framework\TestCase;

final class FatalNotifyerTest extends TestCase
{
	private int $errorReporting;

	/** Number of error handlers set by the test */
	private int $handlers = 0;

	private string $tmp = '';

	protected function setUp(): void
	{
		$this->errorReporting = error_reporting();
		$this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fatalnotifyer_' . uniqid();
	}

	protected function tearDown(): void
	{
		for ($i = 0; $i < $this->handlers; $i++) {
			restore_error_handler();
		}
		error_reporting($this->errorReporting);
		$this->removeDir($this->tmp);
	}

	private function removeDir(string $dir): void
	{
		if(!is_dir($dir)) {
			return;
		}
		foreach (scandir($dir) as $item) {
			if($item === '.' || $item === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			is_dir($path) ? $this->removeDir($path) : unlink($path);
		}
		rmdir($dir);
	}

	/**
	 * A notifyer whose error handler is restored after the test
	 */
	private function notifyer(): FatalNotifyer
	{
		$this->handlers++;
		return new FatalNotifyer;
	}

	public function testDefinesFatalConstant(): void
	{
		new FatalNotifyer;
		$this->assertTrue(defined('E_FATAL'));
		$this->assertSame(FatalNotifyer::FATAL, E_FATAL);
		$this->assertSame(E_ALL, error_reporting());
	}

	public function testSeverityName(): void
	{
		$this->assertSame('E_USER_WARNING', FatalNotifyer::getSeverityName(E_USER_WARNING));
		$this->assertSame('E_STRICT', FatalNotifyer::getSeverityName(2048));
		$this->assertSame('UNKNOWN (3)', FatalNotifyer::getSeverityName(3));
	}

	public function testRegisterThrowsTypedException(): void
	{
		$this->notifyer()->register();

		try {
			trigger_error('my warning', E_USER_WARNING);
			$this->fail('No exception thrown');
		}
		catch (UserWarningException $e) {
			$this->assertSame('my warning', $e->getMessage());
			$this->assertSame(E_USER_WARNING, $e->getSeverity());
			$this->assertSame(__FILE__, $e->getFile());
			$this->assertSame(__LINE__ - 7, $e->getLine());
		}
	}

	public function testRegisterOnlyGivenSeverity(): void
	{
		$this->notifyer()->register(E_USER_NOTICE);

		# Not registered: swallowed
		$this->assertTrue(trigger_error('deprecated', E_USER_DEPRECATED));

		$this->expectException(UserNoticeException::class);
		trigger_error('notice', E_USER_NOTICE);
	}

	public function testUnregisteredErrorIsSwallowed(): void
	{
		$notifyer = $this->notifyer()->register(E_USER_NOTICE);
		$this->assertTrue($notifyer->errorHandler(E_USER_WARNING, 'warning', __FILE__, __LINE__));
	}

	public function testPhpWarningThrows(): void
	{
		$this->notifyer()->register();
		$this->expectException(WarningException::class);
		$array = [];
		$array[] = 1 / 1 + (int) file_get_contents('/this/file/does/not/exist');
	}

	public function testSuppressedErrorIsIgnored(): void
	{
		$this->notifyer()->register();
		$this->assertTrue(@trigger_error('suppressed', E_USER_WARNING));
	}

	public function testNotReportedErrorReturnsFalse(): void
	{
		$notifyer = $this->notifyer()->register();
		error_reporting(E_ALL & ~E_USER_NOTICE);
		$this->assertFalse($notifyer->errorHandler(E_USER_NOTICE, 'notice', __FILE__, __LINE__));
	}

	public function testExceptionsMapping(): void
	{
		$notifyer = $this->notifyer()->register(-1);

		try {
			$notifyer->errorHandler(E_COMPILE_WARNING, 'compile', __FILE__, __LINE__);
			$this->fail('No exception thrown');
		}
		catch (CompileWarningException $e) {
			$this->assertSame(E_COMPILE_WARNING, $e->getSeverity());
		}

		$this->expectException(StrictException::class);
		error_reporting(-1);
		$notifyer->errorHandler(2048, 'strict', __FILE__, __LINE__);
	}

	public function testUnknownSeverityThrowsErrorException(): void
	{
		$notifyer = $this->notifyer()->register(-1);
		error_reporting(-1);
		try {
			$notifyer->errorHandler(1 << 20, 'unknown', __FILE__, __LINE__);
			$this->fail('No exception thrown');
		}
		catch (ErrorException $e) {
			$this->assertSame(ErrorException::class, get_class($e));
		}
	}

	public function testCustomHandlerReplacesException(): void
	{
		$calls = [];
		$this->notifyer()
			->custom(E_USER_WARNING, function (int $severity, string $message, string $fileName, int $line) use (&$calls) {
				$calls[] = [$severity, $message, $fileName, $line];
			})
			->register();

		trigger_error('handled', E_USER_WARNING);

		$this->assertSame([[E_USER_WARNING, 'handled', __FILE__, __LINE__ - 2]], $calls);
	}

	public function testCustomHandlerBitmaskAndExactWins(): void
	{
		$calls = [];
		$this->notifyer()
			->custom(E_USER_WARNING | E_USER_NOTICE, function (int $severity) use (&$calls) {
				$calls[] = 'mask';
			})
			->custom(E_USER_NOTICE, function (int $severity) use (&$calls) {
				$calls[] = 'exact';
			})
			->register();

		trigger_error('warning', E_USER_WARNING);
		trigger_error('notice', E_USER_NOTICE);

		$this->assertSame(['mask', 'exact'], $calls);
	}

	public function testBacktraceOnlyIfDeclared(): void
	{
		$backtraces = [];
		$this->notifyer()
			->custom(E_USER_WARNING, function ($severity, $message, $fileName, $line, $context, $backtrace) use (&$backtraces) {
				$backtraces[] = $backtrace;
			})
			->custom(E_USER_NOTICE, function (...$args) use (&$backtraces) {
				$backtraces[] = $args[5];
			})
			->custom(E_USER_DEPRECATED, function ($severity, $message) use (&$backtraces) {
				$backtraces[] = func_num_args() === 6 ? func_get_arg(5) : null;
			})
			->register();

		trigger_error('warning', E_USER_WARNING);
		trigger_error('notice', E_USER_NOTICE);
		trigger_error('deprecated', E_USER_DEPRECATED);

		$this->assertStringContainsString('errorHandler', $backtraces[0]);
		$this->assertStringContainsString('errorHandler', $backtraces[1]);
		$this->assertSame('', $backtraces[2]);
	}

	public function testListenersAreAllCalledThenErrorContinues(): void
	{
		$calls = [];
		$this->notifyer()
			->listen(E_USER_WARNING | E_USER_NOTICE, function (int $severity, string $message) use (&$calls) {
				$calls[] = "first $message";
			})
			->listen(E_USER_NOTICE, function (int $severity, string $message) use (&$calls) {
				$calls[] = "second $message";
			})
			->listen(E_USER_WARNING, [$this, 'staticListener'])
			->register(E_USER_WARNING);

		trigger_error('notice', E_USER_NOTICE);
		$this->assertSame(['first notice', 'second notice'], $calls);

		try {
			trigger_error('warning', E_USER_WARNING);
			$this->fail('No exception thrown');
		}
		catch (UserWarningException $e) {
			$this->assertSame(['first notice', 'second notice', 'first warning'], $calls);
			$this->assertSame(1, $this->staticCalls);
		}
	}

	private int $staticCalls = 0;

	public function staticListener(int $severity, string $message): void
	{
		$this->staticCalls++;
	}

	public function testListenAloneSetsHandler(): void
	{
		$calls = 0;
		$this->notifyer()->listen(E_USER_NOTICE, function () use (&$calls) {
			$calls++;
		});
		trigger_error('notice', E_USER_NOTICE);
		$this->assertSame(1, $calls);
	}

	public function testEachInstanceSetsItsHandler(): void
	{
		$first = 0;
		$second = 0;
		$this->notifyer()->custom(E_USER_NOTICE, function () use (&$first) { $first++; })->register();
		$this->notifyer()->custom(E_USER_NOTICE, function () use (&$second) { $second++; })->register();

		trigger_error('notice', E_USER_NOTICE);

		$this->assertSame(0, $first);
		$this->assertSame(1, $second);
	}

	public function testFatalHandlerIgnoresHandleableErrors(): void
	{
		$notifyer = new FatalNotifyer;

		error_clear_last();
		$this->assertFalse($notifyer->fatalHandler());

		@trigger_error('suppressed', E_USER_WARNING);
		$this->assertSame(E_USER_WARNING, error_get_last()['type']);
		$this->assertFalse($notifyer->fatalHandler());
		error_clear_last();
	}

	public function testFatalErrorReachesListenersAtShutdown(): void
	{
		$autoload = var_export(dirname(__DIR__) . '/vendor/autoload.php', true);
		$script = <<<PHP
		<?php
		require $autoload;
		ini_set('display_errors', 'off');
		ini_set('log_errors', 'off');
		ini_set('memory_limit', '8M');
		(new Coercive\\Utility\\FatalNotifyer\\FatalNotifyer)
			->listen(E_FATAL, function (int \$severity, string \$message) {
				echo 'LISTENED ' . Coercive\\Utility\\FatalNotifyer\\FatalNotifyer::getSeverityName(\$severity);
			})
			->custom(E_ERROR, function () {})
			->register();
		str_repeat('x', 16 * 1024 * 1024);
		PHP;

		$file = tempnam(sys_get_temp_dir(), 'fatalnotifyer');
		file_put_contents($file, $script);
		exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file) . ' 2>&1', $output);
		unlink($file);

		$this->assertSame(['LISTENED E_ERROR'], $output);
	}

	public function testSaveWritesPersonalLog(): void
	{
		$_GET = ['page' => 'home'];
		$this->notifyer()->save($this->tmp, E_USER_NOTICE);

		trigger_error('saved', E_USER_NOTICE);
		trigger_error('not saved', E_USER_DEPRECATED);

		$log = new FatalLog($this->tmp);
		$days = $log->getDayList();
		$this->assertSame([date('Y-m-d')], $days);

		$files = $log->getFileList($days[0]);
		$this->assertCount(1, $files);

		$errors = $log->getFile($this->tmp . DIRECTORY_SEPARATOR . $days[0] . DIRECTORY_SEPARATOR . $files[0]);
		$this->assertCount(1, $errors);
		$error = reset($errors);
		$this->assertSame(E_USER_NOTICE, $error['severity']);
		$this->assertSame('saved', $error['message']);
		$this->assertStringContainsString('home', $error['GET']);
		$this->assertStringContainsString('errorHandler', $error['backtrace']);
		$_GET = [];
	}
}
