<?php
namespace Tests\Unit\Tartana\Domain\Command;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Entity\Log;
use Tartana\Domain\FileLogRepository;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class FileLogRepositoryTest extends \PHPUnit_Framework_TestCase
{

	const LOG_FILE_NAME = 'log.txt';

	public function testFindLogs ()
	{
		$fs = new Local(__DIR__);

		$logger = new Logger('unittest');
		$logger->pushHandler(new StreamHandler($fs->applyPathPrefix(self::LOG_FILE_NAME)));
		$logger->addAlert('unit test alert', [
				'unittestcontext'
		]);
		$logger->log(Logger::WARNING, 'unit test warning');

		$repository = new FileLogRepository($fs->applyPathPrefix(self::LOG_FILE_NAME));
		$logs = $repository->findLogs();

		$this->assertNotEmpty($logs);
		$this->assertCount(2, $logs);

		$this->assertNotEmpty($logs[0]->getDate());
		$this->assertEquals('unittest', $logs[0]->getChannel());
		$this->assertEquals(Logger::getLevelName(Logger::WARNING), $logs[0]->getLevel());
		$this->assertEquals('unit test warning', $logs[0]->getMessage());
		$this->assertEmpty($logs[0]->getContext());
		$this->assertEmpty($logs[0]->getExtra());

		$this->assertNotEmpty($logs[1]->getDate());
		$this->assertEquals('unittest', $logs[1]->getChannel());
		$this->assertEquals(Logger::getLevelName(Logger::ALERT), $logs[1]->getLevel());
		$this->assertEquals('unit test alert', $logs[1]->getMessage());
		$this->assertEquals('unittestcontext', $logs[1]->getContext()[0]);
		$this->assertEmpty($logs[1]->getExtra());
	}

	public function testFindMany ()
	{
		$fs = new Local(__DIR__);

		$logger = new Logger('unittest');
		$logger->pushHandler(new StreamHandler($fs->applyPathPrefix(self::LOG_FILE_NAME)));
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');

		$repository = new FileLogRepository($fs->applyPathPrefix(self::LOG_FILE_NAME));
		$logs = $repository->findLogs();

		$this->assertNotEmpty($logs);
		$this->assertCount(10, $logs);
	}

	public function testFindWithCount ()
	{
		$fs = new Local(__DIR__);

		$logger = new Logger('unittest');
		$logger->pushHandler(new StreamHandler($fs->applyPathPrefix(self::LOG_FILE_NAME)));
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');
		$logger->log(Logger::WARNING, 'unit test warning');

		$repository = new FileLogRepository($fs->applyPathPrefix(self::LOG_FILE_NAME));
		$logs = $repository->findLogs(20);

		$this->assertNotEmpty($logs);
		$this->assertCount(12, $logs);
	}

	public function testFindLogsEmptyFile ()
	{
		$fs = new Local(__DIR__);
		$fs->write(self::LOG_FILE_NAME, '', new Config());

		$repository = new FileLogRepository($fs->applyPathPrefix(self::LOG_FILE_NAME));
		$logs = $repository->findLogs();

		$this->assertEmpty($logs);
	}

	public function testFindLogsFileNotExists ()
	{
		$repository = new FileLogRepository(__DIR__ . '/invalid.txt');
		$logs = $repository->findLogs();

		$this->assertEmpty($logs);
		$this->assertFileNotExists(__DIR__ . '/invalid.txt');
	}

	public function testFindLogsIncorrectData ()
	{
		$fs = new Local(__DIR__);
		$fs->write(self::LOG_FILE_NAME, 'unit test', new Config());

		$repository = new FileLogRepository($fs->applyPathPrefix(self::LOG_FILE_NAME));
		$logs = $repository->findLogs();

		$this->assertEmpty($logs);
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		if ($fs->has(self::LOG_FILE_NAME))
		{
			$fs->delete(self::LOG_FILE_NAME);
		}
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		if ($fs->has(self::LOG_FILE_NAME))
		{
			$fs->delete(self::LOG_FILE_NAME);
		}
	}
}