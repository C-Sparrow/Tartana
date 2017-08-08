<?php
namespace Tests\Unit\Tartana\Event\Listener;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\ProcessDiscFolderCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ProcessDiscFolderCommandTest extends TartanaBaseTestCase
{

	public function testProcessDirectory()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test/CD/test.mp3', 'mp3', new Config());
		$fs->write('test/test/Cover/test.jpg', 'jpg', new Config());

		$application = new Application();
		$application->add(new ProcessDiscFolderCommand());
		$command = $application->find('process:disc');

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'source' => $fs->applyPathPrefix('test')
		]);

		$this->assertTrue($fs->has('test/test/test.mp3'));
		$this->assertTrue($fs->has('test/test/test.jpg'));
		$this->assertFalse($fs->has('test/test/CD'));
		$this->assertFalse($fs->has('test/test/Cover'));
	}

	public function testProcessDirectoryDeepNested()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test/CD/test.mp3', 'mp3', new Config());
		$fs->write('test/test/Cover/test.jpg', 'jpg', new Config());
		$fs->write('test/test/CD/test1/CD/test.mp3', 'mp3', new Config());
		$fs->write('test/test/CD/test1/Cover/test.jpg', 'jpg', new Config());

		$application = new Application();
		$application->add(new ProcessDiscFolderCommand());
		$command = $application->find('process:disc');

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'source' => $fs->applyPathPrefix('test')
		]);

		$this->assertTrue($fs->has('test/test/test.mp3'));
		$this->assertTrue($fs->has('test/test/test.jpg'));
		$this->assertTrue($fs->has('test/test/CD/test1/test.mp3'));
		$this->assertTrue($fs->has('test/test/CD/test1/test.jpg'));
	}

	public function testProcessDirectoryNotEmpty()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test/CD/test.mp3', 'mp3', new Config());
		$fs->write('test/test/CD/dir/test.txt', 'txt', new Config());
		$fs->write('test/test/Cover/test.jpg', 'jpg', new Config());

		$application = new Application();
		$application->add(new ProcessDiscFolderCommand());
		$command = $application->find('process:disc');

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'source' => $fs->applyPathPrefix('test')
		]);

		$this->assertTrue($fs->has('test/test/test.mp3'));
		$this->assertTrue($fs->has('test/test/test.jpg'));
		$this->assertTrue($fs->has('test/test/CD'));
		$this->assertTrue($fs->has('test/test/CD/dir/test.txt'));
		$this->assertFalse($fs->has('test/test/Cover'));
	}

	public function testProcessDirectoryWrongSource()
	{
		$application = new Application();
		$application->add(new ProcessDiscFolderCommand());
		$command = $application->find('process:disc');

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'source' => __DIR__ . '/wrong'
		]);
	}

	protected function setUp()
	{
		$this->tearDown();
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}
}
