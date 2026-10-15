<?php
namespace Coercive\Utility\FatalNotifyer\Tests;

use Coercive\Utility\FatalNotifyer\FatalLog;
use PHPUnit\Framework\TestCase;

final class FatalLogTest extends TestCase
{
	private string $tmp = '';

	protected function setUp(): void
	{
		$this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fatallog_' . uniqid() . DIRECTORY_SEPARATOR . 'sub';
	}

	protected function tearDown(): void
	{
		$root = dirname($this->tmp);
		if(is_dir($root)) {
			exec('rm -rf ' . escapeshellarg($root));
		}
	}

	public function testCreatesDirectory(): void
	{
		new FatalLog($this->tmp);
		$this->assertDirectoryExists($this->tmp);
	}

	public function testSaveAndRead(): void
	{
		$log = new FatalLog($this->tmp);
		$log->save(E_WARNING, 'first', '/file.php', 12, ['a' => 1], 'trace');
		$log->save(E_NOTICE, "second\nwith a new line and invalid utf8 \xB1", '/file.php', 13, ['closure' => function () {}], '');

		$day = date('Y-m-d');
		$this->assertSame([$day], $log->getDayList());

		$errors = [];
		foreach ($log->getFileList($day) as $file) {
			foreach ($log->getFile($this->tmp . DIRECTORY_SEPARATOR . $day . DIRECTORY_SEPARATOR . $file) as $error) {
				$errors[] = $error;
			}
		}

		$this->assertCount(2, $errors);
		$this->assertSame('first', $errors[0]['message']);
		$this->assertSame(12, $errors[0]['line']);
		$this->assertSame(['a' => 1], $errors[0]['context']);
		$this->assertStringStartsWith("second\nwith a new line", $errors[1]['message']);
	}

	public function testDayListIsSortedNewestFirst(): void
	{
		$log = new FatalLog($this->tmp);
		mkdir($this->tmp . '/2017-07-20');
		mkdir($this->tmp . '/2017-07-22');
		mkdir($this->tmp . '/2017-07-21');
		$this->assertSame(['2017-07-22', '2017-07-21', '2017-07-20'], $log->getDayList());
	}

	public function testUnknownDayAndFile(): void
	{
		$log = new FatalLog($this->tmp);
		$this->assertSame([], $log->getFileList('2000-01-01'));
		$this->assertSame([], $log->getFile($this->tmp . '/nope'));
	}
}
