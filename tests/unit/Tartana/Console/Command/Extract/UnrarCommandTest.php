<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\Extract\UnrarCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use Tartana\Event\ProcessingProgressEvent;
use Tartana\Component\Command\Runner;

class UnrarCommandTest extends TartanaBaseTestCase
{

	public function testExecute()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.rar', 'unit', new Config());
		$fs->write('test/test.txt', 'unit', new Config());

		$command = new UnrarCommand(
			$this->getMockDispatcher([
				'processing.completed',
				$this->anything()
			]),
			$this->getMockRunner(
				[
					$this->callback(function (Command $command) {
						return $command->getCommand() == 'unrar';
					}),
					$this->callback(function (Command $command) {
						return strpos($command, 'unrar l') !== false;
					})
				],
				[
					'All OK',
					'Archive: ' . $fs->applyPathPrefix('test/test.rar')
				]
			),
			new Registry()
		);

		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute(
			[
				'command' => $command->getName(),
				'source' => $fs->applyPathPrefix('test'),
				'destination' => $fs->applyPathPrefix('test1')
			]
		);

		$this->assertFalse($fs->has('test/test.rar'));
		$this->assertTrue($fs->has('test/test.txt'));
	}

	public function testExecuteProgress()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.rar', 'unit', new Config());
		$fs->write('test/test.txt', 'unit', new Config());

		$runner       = new UnrarCommandTestRunner();
		$runner->file = $fs->applyPathPrefix('test/test.rar');

		$command = new UnrarCommand(
			$this->getMockDispatcher(
				[
					[
						$this->equalTo('processing.progress'),
						$this->callback(
							function (ProcessingProgressEvent $event) {
								return $event->getFile() == 'test.rar' && $event->getProgress() == 23;
							}
						)
					],
					[
						$this->equalTo('processing.completed'),
						$this->anything()
					]
				]
			),
			$runner,
			new Registry()
		);

		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute(
			[
				'command' => $command->getName(),
				'source' => $fs->applyPathPrefix('test'),
				'destination' => $fs->applyPathPrefix('test1')
			]
		);
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}

class UnrarCommandTestRunner extends Runner
{

	public $file = null;

	public function execute(Command $command, $callback = null)
	{
		$callback('Extracting from ' . $this->file);
		$callback('test 23%');
	}
}
