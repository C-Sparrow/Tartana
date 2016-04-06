<?php
namespace Tests\Functional\Tartana\Event\Listener;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\ConvertSoundCommand;
use Tartana\Event\ProcessingCompletedEvent;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ConvertSoundCommandTest extends TartanaBaseTestCase
{

	public function testConvertFile ()
	{
		if (! (new Runner())->execute(new Command('which ffmpeg')))
		{
			$this->markTestSkipped('FFmpeg is not on the path!');
			return;
		}

		$fs = new Local(__DIR__);
		$fs->copy('files/test.mp4', 'test/test.mp4');
		$fs->createDir('test1', new Config());

		$application = new Application();
		$application->add(new ConvertSoundCommand(new Runner('test')));
		$command = $application->find('convert:sound');

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);

		$this->assertTrue($fs->has('test/test.mp4'));
		$this->assertTrue($fs->has('test1/test.mp3'));
	}

	public function testConvertInvalidFile ()
	{
		if (! (new Runner())->execute(new Command('which ffmpeg')))
		{
			$this->markTestSkipped('FFmpeg is not on the path!');
			return;
		}

		$dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		$dispatcher->expects($this->once())
			->method('dispatch')
			->with($this->equalTo('processing.completed'),
				$this->callback(function  (ProcessingCompletedEvent $event) {
					return ! $event->isSuccess();
				}));

		$fs = new Local(__DIR__);
		$fs->write('test/test.mp4', 'invalid mp4 content', new Config());
		$fs->createDir('test1', new Config());

		$command = new ConvertSoundCommand(new Runner('test'), $dispatcher);
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);

		$this->assertTrue($fs->has('test/test.mp4'));
		$this->assertTrue($fs->has('test/test.mp4.out'));
		$this->assertFalse($fs->has('test1/test.mp3'));
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
