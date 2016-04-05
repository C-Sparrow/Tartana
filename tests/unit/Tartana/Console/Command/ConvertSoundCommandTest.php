<?php
namespace Tests\Unit\Tartana\Event\Listener;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\ConvertSoundCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Event\ProcessingCompletedEvent;
use Tartana\Component\Command\Runner;

class ConvertSoundCommandTest extends TartanaBaseTestCase
{

	public function testConvertFile ()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.mp4', 'mp4', new Config());
		$fs->write('test/test.txt', 'hello', new Config());
		$fs->createDir('test1', new Config());

		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, 'which ffmpeg') !== false;
						}),
						[
								$this->callback(
										function  (Command $command) {
											return strpos($command, 'ffmpeg') !== false && ! $command->isAsync();
										}),
								$this->callback(
										function  ($callback) {
											$callback('unit test');
											return $callback != null;
										})
						]
				], [
						'yes'
				]);
		$application = new Application();
		$application->add(new ConvertSoundCommand($runner));
		$command = $application->find('convert:sound');

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);

		// For some reasons phpunit is calling the with function twice
		// https://github.com/sebastianbergmann/phpunit-mock-objects/issues/261
		$this->assertEquals('unit test' . PHP_EOL . 'unit test', trim($commandTester->getDisplay()));
	}

	public function testConvertFileWithDispatcher ()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.mp4', 'mp4', new Config());
		$fs->write('test/test.txt', 'hello', new Config());
		$fs->createDir('test1', new Config());

		$dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		$dispatcher->expects($this->once())
			->method('dispatch')
			->with($this->equalTo('processing.completed'),
				$this->callback(function  (ProcessingCompletedEvent $event) {
					return $event->isSuccess();
				}));
		$runner = $this->getMockBuilder(Runner::class)->getMock();
		$runner->method('execute')->willReturn('ffmpeg');

		$application = new Application();
		$application->add(new ConvertSoundCommand($runner, $dispatcher));
		$command = $application->find('convert:sound');

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);
	}

	public function testConvertFileInvalidSource ()
	{
		$fs = new Local(__DIR__);
		$fs->createDir('test1', new Config());

		$application = new Application();
		$application->add(new ConvertSoundCommand($this->getMockRunner()));
		$command = $application->find('convert:sound');

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => __DIR__ . '/invalid',
						'destination' => $fs->applyPathPrefix('test1')
				]);
	}

	public function testConvertFileInvalidDestination ()
	{
		$fs = new Local(__DIR__);
		$fs->createDir('test', new Config());

		$application = new Application();
		$application->add(new ConvertSoundCommand($this->getMockRunner([], [
				'ffmpeg'
		])));
		$command = $application->find('convert:sound');

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);
	}

	public function testConvertFileFFMpegNotAvailable ()
	{
		$fs = new Local(__DIR__);
		$fs->createDir('test', new Config());
		$fs->createDir('test1', new Config());

		$application = new Application();
		$application->add(
				new ConvertSoundCommand(
						$this->getMockRunner(
								[
										$this->callback(function  (Command $command) {
											return true;
										})
								])));
		$command = $application->find('convert:sound');

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
