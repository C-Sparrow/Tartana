<?php
namespace Test\Unit\Tartana\Handler;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\DeleteLogs;
use Tartana\Handler\DeleteFileLogsHandler;

class DeleteFileLogsHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testDeleteLogs()
	{
		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.txt', 'hello', new Config());
		$handler = new DeleteFileLogsHandler($fs->applyPathPrefix('test.txt'));
		$handler->handle(new DeleteLogs());

		$this->assertFalse($fs->has('test.txt'));
	}

	public function testDeleteLogsFileDoesNotExists()
	{
		$handler = new DeleteFileLogsHandler(__DIR__ . '/invalid.txt');
		$handler->handle(new DeleteLogs());

		$this->assertFileNotExists(__DIR__ . '/invalid.txt');
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}
}
