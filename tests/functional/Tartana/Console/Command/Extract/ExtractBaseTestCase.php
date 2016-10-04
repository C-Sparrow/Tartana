<?php
namespace Tests\Functional\Tartana\Console\Command\Extract;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Runner;
use Tests\Unit\Tartana\TartanaBaseTestCase;

abstract class ExtractBaseTestCase extends TartanaBaseTestCase
{

	protected $archivesPath = null;

	public function testExecute()
	{
		if (! $this->copyArchives()) {
			return;
		}

		$application = new Application();
		$command = $this->createCommand($this->getMockDispatcher(), new Runner('test'));
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
			]
		);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertFalse($fs->has('test'));
	}

	public function testExecuteMultipart()
	{
		if (! $this->copyArchives('multipart')) {
			return;
		}

		$application = new Application();
		$command = $this->createCommand($this->getMockDispatcher(), new Runner('test'));
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			array(
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->getPathPrefix() . 'test1'
			)
		);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertFalse($fs->has('test'));
	}

	public function testExecuteWithPasswordFile()
	{
		if (! $this->copyArchives('password')) {
			return;
		}

		$application = new Application();
		$command = $this->createCommand($this->getMockDispatcher(), new Runner('test'));
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$fs->delete('test/pw.txt');
		$commandTester->execute(
			array(
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->getPathPrefix() . 'test1',
						'pwfile' => __DIR__ . '/' . $this->archivesPath . '/password/pw.txt'
			)
		);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertFalse($fs->has('test'));
	}

	public function testExecuteNotEmpty()
	{
		if (! $this->copyArchives()) {
			return;
		}

		$application = new Application();
		$command = $this->createCommand($this->getMockDispatcher(), new Runner('test'));
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$fs->write('test/test.txt', 'Hello unit test', new Config());
		$commandTester->execute(
			array(
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->getPathPrefix() . 'test1',
						'pwfile' => __DIR__ . '/' . $this->archivesPath . '/password/pw.txt'
			)
		);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertTrue($fs->has('test/test.txt'));
		$this->assertFalse($fs->has('test/extract.out'));
		$this->assertEquals('Hello unit test', $fs->read('test/test.txt')['contents']);
	}

	public function testExecuteNotDelete()
	{
		if (! $this->copyArchives('password')) {
			return;
		}

		$application = new Application();
		$command = $this->createCommand(
			$this->getMockDispatcher(),
			new Runner('test'),
			new Registry([
						'extract' => [
								'delete' => false
						]
			])
		);
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->getPathPrefix() . 'test1',
						'pwfile' => __DIR__ . '/' . $this->archivesPath . '/password/pw.txt'
			]
		);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertTrue($fs->has('test'));
		$this->assertFalse($fs->has('test/extract.out'));
	}

	public function testExtractWithOtherFiles()
	{
		if (! $this->copyArchives()) {
			return;
		}

		$fs = new Local(__DIR__);
		$fs->write('test/test.txt', 'Hello unit test', new Config());
		$fs->write('test1/test.txt', 'Hello unit test 2', new Config());

		$application = new Application();
		$command = $this->createCommand($this->getMockDispatcher(), new Runner('test'));
		;
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			array(
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->getPathPrefix() . 'test1'
			)
		);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertTrue($fs->has('test/test.txt'));
		$this->assertFalse($fs->has('test/extract.out'));
		$this->assertEquals('Hello unit test', $fs->read('test/test.txt')['contents']);
		$this->assertEquals('Hello unit test 2', $fs->read('test1/test.txt')['contents']);
	}

	public function testExtractCorrupt()
	{
		if (! $this->copyArchives('corrupt')) {
			return;
		}

		$application = new Application();
		$command = $this->createCommand($this->getMockDispatcher(), new Runner('test'));
		;
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			array(
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->getPathPrefix() . 'test1'
			)
		);

		$this->assertFalse($fs->has('test1/Downloads/symfony.png'));
		$this->assertTrue($fs->has('test'));
		$this->assertTrue($fs->has('test/extract.out'));
	}

	public function testExecuteWithDispatcher()
	{
		if (! $this->copyArchives()) {
			return;
		}

		$dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		$dispatcherMock->method('dispatch')->willReturn(true);
		$dispatcherMock->expects($this->atLeastOnce())
			->method('dispatch');

		$application = new Application();
		$command = $this->createCommand($dispatcherMock, new Runner('test'));
		$application->add($command);
		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
			]
		);
	}

	public function testExecuteErrorWithDispatcher()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');

		$dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		$dispatcherMock->method('dispatch')->willReturn(true);
		$dispatcherMock->expects($this->once())
			->method('dispatch')
			->with($this->equalTo('processing.completed'));

		$application = new Application();
		$command = $this->createCommand($dispatcherMock, new Runner('test'));
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
			[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
			]
		);
	}

	abstract protected function createCommand(EventDispatcherInterface $dispatcher, Runner $runner, Registry $config = null);

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}

	protected function getMockDispatcher($callbacks = [])
	{
		return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
	}

	protected function copyArchives($folder = 'simple')
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');

		if (! $fs->has($this->archivesPath . '/' . $folder)) {
			$this->markTestSkipped('Not supported by extractor');
			return false;
		}

		foreach ($fs->listContents($this->archivesPath . '/' . $folder, false) as $rar) {
			if ($rar['type'] != 'file') {
				continue;
			}
			$fs->copy($rar['path'], str_replace($this->archivesPath . '/' . $folder, 'test/', $rar['path']));
		}
		return true;
	}
}
