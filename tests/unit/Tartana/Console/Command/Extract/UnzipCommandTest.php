<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\Extract\UnzipCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class UnzipCommandTest extends TartanaBaseTestCase
{

	public function testExecute()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.zip', 'unit', new Config());
		$fs->write('test/test.txt', 'unit', new Config());

		$command = new UnzipCommand(
			$this->getMockDispatcher([
				'processing.completed',
				$this->anything()
			]),
			$this->getMockRunner(
				[
								$this->callback(function (Command $command) {
									return $command->getCommand() == 'which';
								}),
								$this->callback(function (Command $command) {
									return $command->getCommand() == 'unzip';
								})
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

		$this->assertFalse($fs->has('test/test.zip'));
		$this->assertTrue($fs->has('test/test.txt'));
	}

	public function testExecuteWith7z()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.zip', 'unit', new Config());
		$fs->write('test/test.txt', 'unit', new Config());

		$command = new UnzipCommand(
			$this->getMockDispatcher([
				'processing.completed',
				$this->anything()
			]),
			$this->getMockRunner(
				[
								$this->callback(function (Command $command) {
									return $command->getCommand() == 'which';
								}),
								$this->callback(function (Command $command) {
									return $command->getCommand() == '7z';
								})
						],
				[
								'7z',
								'Everything is Ok'
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

		$this->assertFalse($fs->has('test/test.zip'));
		$this->assertTrue($fs->has('test/test.txt'));
	}

	public function itest7z()
	{
		$application = new Application();
		$command = new UnzipCommand(
			$this->getMockDispatcher(),
			parent::getMockRunner(
				[
								$this->callback(function (Command $command) {
									return $command->getCommand() == 'which';
								}),
								$this->anything()
						],
				[
								'found'
					]
			),
			new Registry()
		);
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

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
